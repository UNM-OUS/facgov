<?php
ini_set('max_execution_time', 60);
$noun = $package->noun();
$fs = $cms->helper('filestore');
$s = $cms->helper('strings');

//start info file
$infoFile = [];
$infoFile[] = 'DOWNLOADED FROM:';
$infoFile[] = '';
$infoFile[] = $noun->name();
$infoFile[] = $noun->url();
$infoFile[] = '';

//generate filename holding org, meeting date, this noun's modified date
//also add meeting and org info to info file
$filename = [];
if ($meeting = $noun->meeting()) {
    $infoFile[] = $meeting->name();
    $infoFile[] = $meeting->url();
    $infoFile[] = '';
    if ($org = $meeting->organization()) {
        $filename[] = $org->name();
        $infoFile[] = $org->name();
        $infoFile[] = $org->url();
        $infoFile[] = '';
    }
    $filename[] = date('Ymd', $meeting['meeting.start']);
    $filename[] = $meeting->name();
}
$filename[] = $noun->name();
$filename[] = date('Ymd', $noun['dso.modified.date']);
$filename = strtolower(implode('-', $filename));
$filename = preg_replace('/[^a-z0-9\-\_]+/', '_', $filename);
$filename .= '.zip';

//set up zip file
$zipfn = $cms->config['paths.cache'].'/'.$noun['dso.id'].'.zip';
if (file_exists($zipfn)) {
    unlink($zipfn);
}
$zip = new \ZipArchive;
$zip->open($zipfn, \ZipArchive::CREATE);

//add content to zip file and info file
$infoFile[] = 'FILE MANIFEST:';
$infoFile[] = '';

//add main files
foreach ($fs->list($noun, 'main') as $f) {
    $infoFile[] = $f->nameWithHash();
    $infoFile[] = 'uploaded: '.$s->dateTime($f->time());
    $infoFile[] = '';
    $zip->addFile($f->path(), $f->nameWithHash());
}

//add supporting files
foreach ($fs->list($noun, 'supporting') as $f) {
    $infoFile[] = 'supporting/'.$f->nameWithHash();
    $infoFile[] = 'uploaded: '.$s->dateTime($f->time());
    $infoFile[] = '';
    $zip->addFile($f->path(), 'supporting/'.$f->nameWithHash());
}

//add info file to zip
$zip->addFromString('_info.txt', implode("\r\n", $infoFile));

//close zip file and add it to package
$zip->close();
$package->makeMediaFile($filename);
$package->binaryContent(file_get_contents($zipfn));

//remove zip file, because its content is saved in the package now
unlink($zipfn);
