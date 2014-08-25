# PAK Converter

A utility to convert Pidgin's old `theme` files to PHPBB3's `.pak` emoticon files.

## Usage

PAK Converter is written in [PHP](http://php.net), so to use it, you must first have PHP installed and available on your terminal.

Basic usage of PAK Converter might look like this.

    php pakconvert.php --source theme --result smilies.pak

This will convert the file named `theme` and place the results in `smilies.pak`.

The following commandline options are available:

 - `--source` - The path to the source file, required
 - `--result` - The path to the result file, required
 - `--path` - The folder in which the emoticons are located, default is the current folder
 - `-a` or `--append` - If supplied, the converted output will be appended to the result file

PAK Converter *must have* read access to the emoticon image files, as `.pak` files must include the width and height of the images. By default, PAK Converter will look for the images specified in the `theme` file in the current directory, however, this can be changed with the `--path` option.

### WARNING!

The Pidgin theme file parsing is relatively simple and probably won't always work, as it is relatively simple. For this reason, it is recommended to go through the theme file first and remove all unnecessary lines (e.g. comments, section headers, meta information, etc).

PAK Converter is open source. Feel free to use it for anything, or modify it and re-distribute it.
