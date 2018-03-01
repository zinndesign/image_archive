<?
error_reporting(E_ERROR | E_WARNING | E_PARSE);
date_default_timezone_set('America/New_York');

/*******************************************************************************
 * Script name: psd2png_export.php
 * Purpose: This command-line script accepts a Photoshop PSD file as its sole
 * required argument, and uses the ImageMagick "identify" command to determine 
 * if the file has any layers with specific names (silo, shadow or base). If so, 
 * each is exported as a PNG (with alpha transparency for silo and shadow),
 * using the appropriate naming convention.
 *
 * March 2018 update: silo and shadow layers are combined into one file with
 * the name format <filename>_sm.png. In addition, single-layer PSDs are also
 * exported (identical to the PNGs, except for the format).
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

// define the ImageMagick binary paths - may need to update depending on client Mac configuration
define('CONVERT_PATH', '/usr/local/bin/convert');
define('IDENTIFY_PATH', '/usr/local/bin/identify');

// remove first argument
array_shift($argv);

// get and use remaining arguments
$psd = trim( $argv[0] );
$psd = str_replace('\\','', $psd);

// we'll write to a sub-folder so action doesn't try to modify new files as they're added
$outpath = dirname($psd) . '/output/';
if(!file_exists($outpath)) {
    mkdir($outpath); // watch for permissions issues
}

$log = $outpath . 'debug.log';
write2log($log, "Input file: " . basename($psd));

if( !file_exists($psd) ) { // does it exist?
	write2log($log, 'ERROR: Input file not found');
    die();
} elseif( strtolower( substr($psd, -3) ) != 'psd' ) { // is it a PSD?
	write2log($log, 'ERROR: Input file must be a PSD');
    die();
}

// create an array of all layer names
$command = IDENTIFY_PATH . " -quiet -format \"%[label]^\" $psd";
write2log($log, $command);
$result = `$command`;
$layers = explode('^', $result);

// delete the extra element
array_pop($layers);

// give the initial layer a label
$layers[0] = 'composite';

write2log($log, "Layers in PSD file:\n" . print_r($layers, true));

// layer name patterns to match - using regex for typo forgiveness
$match_layers = array('sm' => '/^s[[:alnum:]]{1}lo.*|^sh[[:alnum:]]{1}dow.*/i', // silo and shadow
                      //'sl' => '/^s[[:alnum:]]{1}lo.*/i', // silo - skipping as of 3/18
                      //'sh' => '/^sh[[:alnum:]]{1}dow.*/i', // shadow - skipping as of 3/18
                      'base' => '/^b[[:alnum:]]{1}se.*/i'); // base

// loop through the match layers and export PNGs as appropriate
foreach($match_layers as $key => $pattern) {
    $matches = preg_grep($pattern, $layers);
    
    // if there are matches, create the new PNG
    if(count($matches) > 0) {
        write2log($log, count($matches) . " layer" . (count($matches)>1?'s':'') . " matching regex pattern for '$key' ($pattern)");
        write2log($log, print_r($matches, true));
        
        // format the new filenames - skip any add-on if it's 'base'
        $png_out = $outpath . substr(basename($psd), 0, -4) . ($key == 'base' ? '' : '_'.$key) . '.png';
        $psd_out = substr($png_out, 0, -3) . 'psd';
        write2log($log, $png_out);
        write2log($log, $psd_out);
        
        // create the list of merge layers using the array keys
        $merge = array_keys($matches);
        // to maintain the canvas size, composite layer is included
        array_unshift($merge, 0);
        $merge_layers = $psd . '[' .implode(',', $merge) . ']';
        
        // run the convert command for png
        $command = CONVERT_PATH . " -compose Over $merge_layers \( -clone 0 -alpha transparent \) -swap 0 +delete -background None -layers merge $png_out";
        write2log($log, $command);
        `$command`;
        
        // run the convert command for psd - can just convert the png
        $command = CONVERT_PATH . " $png_out $psd_out";
        write2log($log, $command);
        `$command`;
         
         // wrap it up
         write2log($log, ">>>>>>>>>>>>>> $key PNG file saved as " . basename($png_out) . " <<<<<<<<<<<<<<" );
         write2log($log, ">>>>>>>>>>>>>> $key PSD file saved as " . basename($psd_out) . " <<<<<<<<<<<<<<" );
    } else {
        write2log($log, "No layers matching regex pattern for '$key' ($pattern)");
    }
}

function write2log($logfile, $message) {
    $now = date("Y-m-d H:i:s");
    file_put_contents($logfile, $now . " - " .$message . "\n", FILE_APPEND | LOCK_EX);
}
