<?php
/**
 * # PAK Converter
 * 
 * A utility to convert Pidgin's old `theme` files to PHPBB3's `.pak` emoticon files.
 * 
 * ## Usage
 * PAK Converter is written in [PHP](http://php.net), so to use it, you must first have PHP installed
 * and available on your terminal.
 * 
 * Basic usage of PAK Converter might look like this.
 * 
 *     php pakconvert.php --source theme --result smilies.pak
 * 
 * This will convert the file named `theme` and place the results in `smilies.pak`.
 * 
 * The following commandline options are available:
 * 
 *  - `--source` - The path to the source file, required
 *  - `--result` - The path to the result file, required
 *  - `--path` - The folder in which the emoticons are located, default is the current folder
 *  - `-a` or `--append` - If supplied, the converted output will be appended to the result file
 * 
 * PAK Converter *must have* read access to the emoticon image files, as `.pak` files must include the
 * width and height of the images. By default, PAK Converter will look for the images specified in the
 * `theme` file in the current directory, however, this can be changed with the `--path` option.
 * 
 * ### WARNING!
 * The Pidgin theme file parsing is relatively simple and probably won't always work, as it is relatively
 * simple. For this reason, it is recommended to go through the theme file first and remove all unnecessary
 * lines (e.g. comments, section headers, meta information, etc).
 * 
 * PAK Converter is open source. Feel free to use it for anything, or modify it and re-distribute it.
 * 
 *
 * @author Tom Barham <me@mrfishie.com>
 * @version 0.1.0
 */

// Time script execution
$startTime = microtime(true);
 
$options = getopt("a", array("source:", "result:", "path::", "append"));

// Validate inputs
if (array_key_exists("source", $options)) $sourceFile = $options["source"];
else die("[ERROR] No source value supplied!\n");

if (array_key_exists("result", $options)) $resultFile = $options["result"];
else die("[ERROR] No result value supplied!\n");

if (array_key_exists("path", $options)) $imagePath = $options["path"];
else $imagePath = "." . DIRECTORY_SEPARATOR;

// Validate paths
if (!file_exists($sourceFile)) die("[ERROR] The source file does not exist!\n");
if (!is_dir($imagePath)) die("[ERROR] The image folder does not exist or is not a directory!\n");

// Sanitize image path
$imagePath = str_replace("/", DIRECTORY_SEPARATOR, str_replace("\\", DIRECTORY_SEPARATOR, $imagePath));
if (strrpos($imagePath, DIRECTORY_SEPARATOR) !== strlen($imagePath) - strlen(DIRECTORY_SEPARATOR)) $imagePath .= DIRECTORY_SEPARATOR;

echo "[INFO] Loading theme file from '$sourceFile'...\n";

$sourceContents = preg_replace('/[[:blank:]]+/', ' ', file_get_contents($sourceFile));
$sourceLength = strlen($sourceContents);
echo "[INFO] Finished reading $sourceLength character(s).\n[INFO] Beginning parse...\n";

// Split into lines
$sourceLines = explode("\n", $sourceContents);
$parsedSource = array();

// Split each line
$ignoreChars = array(
    "#", "["
);


foreach ($sourceLines as $line) {
    // Ignore any empty lines
    if (trim($line) !== "") {
        $hasIgnoreChar = false;
        
        // Find if we should ignore the line
        foreach ($ignoreChars as $ignoreChar) {
            if (strpos($line, $ignoreChar) === 0) {
                $hasIgnoreChar = true;
                break;
            }
        }
        
        if (!$hasIgnoreChar) array_push($parsedSource, explode(" ", $line));
    }
}

$individualCount = count($parsedSource);
echo "[INFO] Found $individualCount individual line(s).\n[INFO] Condensing duplicates...\n";

// Merge duplicate entires so that we have one item for each image, with only one copy of each code
$duplicateFind = array();
foreach ($parsedSource as $parsedLine) {
    $parsedName = $parsedLine[0];
    if (array_key_exists($parsedName, $duplicateFind)) {        
        $parsedEmoticons = array_slice($parsedLine, 1);
        
        $alreadyEmoticons = array_slice($duplicateFind[$parsedName], 1);
        foreach ($parsedEmoticons as $emoti) {
            if (!in_array($emoti, $alreadyEmoticons)) array_push($duplicateFind[$parsedName], $emoti);
        }
    } else $duplicateFind[$parsedName] = $parsedLine;
}
echo "[INFO] Found " . count($duplicateFind) . " individual emoticon(s).\n[INFO] Converting format...\n";

// Create an array of formatted strings
$condensedLines = array();
foreach ($duplicateFind as $parsedLine) {
    $imageName = $imagePath . $parsedLine[0];
    if (!file_exists($imageName)) die("[ERROR] The image '$imageName' does not exist!");
    
    list($imageWidth, $imageHeight) = getimagesize($imageName);
    
    // Try to determine a nice-ish name
    $niceFilename = preg_replace('/[^a-zA-Z0-9\s]/', ' ', pathinfo($imageName, PATHINFO_FILENAME));
    $niceImageName = ucwords($niceFilename);
    
    $emoticonList = array_slice($parsedLine, 1);
    $firstEmoticon = true;
    foreach ($emoticonList as $emoticonName) {
        if (trim($emoticonName) !== '') {
            $infoLine = array($parsedLine[0], $imageWidth, $imageHeight, $firstEmoticon ? 1 : 0, $niceImageName, $emoticonName);
            $firstEmoticon = false;
            
            array_push($condensedLines, implode(", ", array_map(function($n) {
                return "'" . str_replace("'", "\\'", $n) . "'";
            }, $infoLine)));
        }
    }
}

// Display some info and save
$condensedCount = count($condensedLines);
echo "[INFO] Generated $condensedCount pak line(s)";
$countDiff = $condensedCount - $individualCount;
if ($countDiff !== 0) echo " - " . ($countDiff > 0 ? "Inflation": "Deflation") . " of " . abs($countDiff) . " line(s)";
echo ".\n";

array_push($condensedLines, "");
$finalText = implode(", \n", $condensedLines);
$finalLength = strlen($finalText);
echo "[INFO] Saving $finalLength character(s) to file...";

$lengthDiff = $finalLength - $sourceLength;
if ($lengthDiff !== 0) echo " " . ($lengthDiff > 0 ? "Inflation" : "Deflation") . " of " . abs($lengthDiff) . " character(s).";
echo "\n[INFO] Average of " . round($finalLength / $condensedCount, 2) . " character(s) per line.\n";

// Append to the file if the append flag is set
if (array_key_exists("append", $options) || array_key_exists("a", $options)) file_put_contents($resultFile, "\n" . $finalText, FILE_APPEND);
else file_put_contents($resultFile, $finalText);

$endTime = microtime(true);
$totalTime = $endTime - $startTime;

echo "[INFO] Done! Conversion took " . round($totalTime, 4) . " second(s).\n";
?>