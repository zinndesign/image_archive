# Using the PSD2PNG script
The PSD2PNG utility script was created to reduce large Photoshop files for archiving purposes. The script takes a layered Photoshop file as input, and generates new files based on the following logic:

* Layers with names matching **silo** or **shadow** are merged as they are ordered, and exported to both a PNG file and a single-layer PSD file. The files are named using the original file name, with the characters **_sm** appended.
* Layers with the name **base** are merged and exported in the same fashion. However, the exported PNG and PSD files retain the original filename.

Further details:

* The exported files are saved into a sub-folder named `processed`. This is both for hot folder compatibility (explained below) and to avoid overwriting the original PSD file.
* The layer matching uses regular expressions, rather than exact matches, to allow for typos, numbering, etc. (e.g. silo1, silo2).
* The original script exported **silo** and **shadow** layers to separate files (filename_sl.png and filename_sh.png, respectively). The logic for this is still in the script, but commented out.

## Using the script as a standalone
The script is run from the command line, and can be run independently by passing the path to a PSD file as an argument, for example:

`php psd2png.php ~/COS/PSD/COS010118WPYCOSMOBITES_001.psd`

The script uses ImageMagick to extract the layer names from the PSD, match them against the regular expressions, and export the merged PSD and PNG files as appropriate.

Note that while PHP is part of the standard install for Mac OS X, __ImageMagick__ will need to be installed. This is most easily done via a package manager like [Homebrew](https://brew.sh).

## Applying the script as a folder action
On Mac OS X, the [Automator](https://developer.apple.com/library/content/documentation/AppleApplications/Conceptual/AutomatorConcepts/Automator.html) program allows you to trigger "actions" when a file is added to a folder (often referred to as a "hot folder"). This repository contains a directory named `PSD2PNG.workflow` which can be opened in Automator to create a _service_ that can be applied to folders in the Finder program.

The PSD2PNG folder action triggers a shell script that is run whenever a new file is added to the folder. The code for the action is below:

```
for f in "$@"
do
	/usr/bin/php ~/Desktop/psd2png.php "$f"
done
```
The only parts that may need to be altered are the paths. The path to PHP can be determined by entering `which php` in a Terminal window. The path to the `psd2png.php` script is arbitrary, depending on where the user chooses to save it. The script must exist at the path specified or the action will fail.

Once its set up, the action can be applied to a folder by right-clicking on it in Finder and selecting `Services > Folder Actions Setup`. The `PSD2PNG.workflow` action should be available in the list of actions that pops up.

## Cloning or downloading the repository
There are a couple of options for copying these files to your local machine. The repository page on GitHub has a "Download or Clone" button, which allows you to either:

* Download a zip containing all of the files in the repository; or
* Clone the repository using Git: `git clone https://github.com/zinndesign/image_archive.git`

The advantage to cloning the repository is that it maintains a link to the remote source - so if changes are made, a simple call to `git pull` will update the local files.

However, if the script will not be updated frequently - or if the intention is to move it to a different folder - downloading a zip will suffice.

_Last update: 03/02/2018_