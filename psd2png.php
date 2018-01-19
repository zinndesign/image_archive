#!/usr/bin/php
<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

/*******************************************************************************
 * Script name: psd2png_export.php
 * Purpose: This command-line script accepts a Photoshop PSD file as its sole
 * required argument, and uses the ImageMagick "identify" command to determine 
 * if the file has any layers with specific names (silo, shadow or base). If so, 
 * each is exported as a PNG (with alpha transparency for silo and shadow),
 * using the appropriate naming convention.
 *
 * Binary Dependencies:
 * - PHP 5.6.30 (cli) (built: Feb  7 2017 16:18:37)
 * - ImageMagick 7.0.7-21 Q16 x86_64 2018-01-08 http://www.imagemagick.org
 *
 * Reference links:
 * https://www.imagemagick.org/discourse-server/viewtopic.php?t=25863
 * http://undertheweathersoftware.com/how-to-extract-layers-from-a-photoshop-file-with-imagemagick-and-python/
 * http://metapicz.com/#landing (to read XMP data)
 *******************************************************************************/

$convert_path = trim(`which convert`); // for debugging ImageMagick issues
$exiftool_path = trim(`which exiftool`); // for debugging exiftool issues

// check for all required arguments
// first argument is always name of script!
if ($argc < 2) {
    die("\nUsage: php psd2png_export.php <\"path to PSD file\">\n\n");
}

// remove first argument
array_shift($argv);

// get and use remaining arguments
$psd = trim( $argv[0] );
$psd = str_replace('\\','', $psd);

if( !file_exists($psd) ) { // does it exist?
	die("\nERROR: Input file not found\n\n");
} elseif( strtolower( substr($psd, -3) ) != 'psd' ) { // is it a PSD?
	die("\nERROR: Input file must be a PSD\n\n");
}

// create an array of all layer names
$result = `identify -quiet -format "%[label]#" $psd`;
$layers = explode('#', $result);

// delete the extra element
array_pop($layers);

// give the initial layer a label
$layers[0] = 'composite';

echo "Layers in PSD file:\n";
print_r($layers);

// we know IM treats 0 as the composite, so let's add it to the start of the array
//array_unshift($layers, "composite");

// layer name patterns to match - using regex for typo forgiveness
$match_layers = array('silo' => '/^s[[:alnum:]]{1}lo.*/i', // silo
                      'shdw' => '/^sh[[:alnum:]]{1}dow.*/i', // shadow
                      'base' => '/^b[[:alnum:]]{1}se.*/i'); // base

// loop through the match layers and export PNGs as appropriate
foreach($match_layers as $key => $pattern) {
    $matches = preg_grep($pattern, $layers);
    
    // if there are matches, create the new PNG
    if(count($matches) > 0) {
        echo count($matches) . " layer" . (count($matches)>1?'s':'') . " matching '$key'\n";
        print_r($matches);
        
        // format the new filename - skip any add-on if it's 'base'
        //$png = substr($psd, 0, -4) . ($key == 'base' ? '' : '_'.$key) . '.png';
        $png = substr($psd, 0, -4) . '_' . $key . '.png';
         
         // create the list of merge layers using the array keys
         $merge = array_keys($matches);
         // to maintain the canvas size, composite layer is included
         array_unshift($merge, 0);
         $merge_layers = $psd . '[' .implode(',', $merge) . ']';
         
         // run the IM command
         // convert <filename>.psd[0] <filename>.psd[2] ( -clone 0 -alpha transparent ) -swap 0 +delete -coalesce -compose src-over -composite <extracted-filename>.png
         $command = "convert -compose Over $merge_layers \( -clone 0 -alpha transparent \) -swap 0 +delete -background None -layers merge $png";
         echo $command . "\n\n";
         `$command`;
         
         // wrap it up
         echo ">>>>>>>>>>>>>> $key PNG file saved as " . basename($png) . "\n\n";
    } else {
        echo "No layers matching '$key'\n\n";
    }
}
?>
