#!/usr/bin/php
<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

/*******************************************************************************
 *
 * Script name: atlas_psd2png.php
 * Purpose: This command-line script accepts a Photoshop PSD file as its sole
 * required argument, and uses the ImageMagick "identify" command to determine 
 * if the file has any transparent or semi-transparent pixels. If it does not, 
 * then a 24-bit PNG file is created from the original image. Optionally, a
 * second argument may be passed ("replace") to indicate the script should
 * delete the original PSD image, leaving only the new PNG file
 * 
 *******************************************************************************/

$script_dir = getcwd();
$convert_path = trim(`which convert`); // for debugging ImageMagick issues

// check for all required arguments
// first argument is always name of script!
if ($argc < 2) {
    die("\nUsage: php atlas_psd2png.php <\"path to PSD file\" (required)> <replace (optional)>\n\n");
}

// remove first argument
array_shift($argv);

// get and use remaining arguments
$image = trim( $argv[0] );
$image = str_replace('\\','', $image);

if( !file_exists($image) ) { // does it exist?
	die("\nERROR: Input file not found\n\n");
} elseif( strtolower( substr($image, -3) ) != 'psd' ) { // is it a PSD?
	die("\nERROR: Input file must be a PSD\n\n");
}

$png_image = substr($image, 0, -4) . '.png';

// for imagemagick, this gives us the visible layers only
$psd_image = $image . '[0]';

// check image for transparency
$command = "identify -format '%[opaque]' \"$psd_image\"";
$opaque = `$command`;

// if conditions are met, save as a JPEG
if( stripos($opaque, 'true') > -1 ) {
	echo "RESULT: PSD has no transparency - creating PNG version\n";
	$command = "convert \"$psd_image\" -verbose \"$png_image\"";
	`$command`;
	echo "PATH TO PNG VERSION: $png_image\n\n";
} else {
	die("RESULT: Input PSD has transparency - PNG conversion aborted.\n\n");
}

// are we deleting the original PSD file?
if(isset($argv[1]) && trim($argv[1])=='replace') {
	echo "Original PSD file deleted\n";
	$command = "rm -f \"$image\"";
	`$command`;
}

?>
