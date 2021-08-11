<?php
$noun = $package->noun();
$fs = $cms->helper('filestore');

//output single file if there's only one
$files = $fs->list($noun, 'main');
$files = $files + $fs->list($noun, 'supporting');
if (count($files) === 1 && !trim($noun['digraph.body.text'])) {
    if ($noun->isEditable()) {
        $cms->helper('notifications')->notice(
            'This page only has one file. Non-admin users would download this file immediately. You are not to allow you access to editing functions.'
        );
    } else {
        $fs->output($package, array_pop($files));
        return;
    }
}

//generate files HTML
$count = 0;
ob_start();
$count += printFiles($cms, $noun, 'main', 'Files');
$count += printFiles($cms, $noun, 'supporting', 'Supporting files');
$filesHTML = ob_get_contents();
ob_end_clean();

//display zip link if file count > 2
if ($count > 2) {
    $zipLink = $noun->url('zip');
    echo "<p><a href='$zipLink'>Download all files as a Zip archive</a></p>";
}

//display files HTML
echo $filesHTML;

function printFiles($cms, $noun, $cat, $title)
{
    $fs = $cms->helper('filestore');
    if (!($files = $fs->list($noun, $cat))) {
        return 0;
    }
    echo "<h2>$title</h2>";
    foreach ($files as $file) {
        echo $file->metaCard();
    }
    return count($files);
}
