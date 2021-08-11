<?php
$time = time();
$s = $cms->helper('strings');
$n = $cms->helper('notifications');
$package['fields.page_title'] = $s->date($time).' '.$package->noun()->name().' roster';

//display hiatus information
if ($hiatus = $package->noun()->hiatus($time)) {
    echo $hiatus->infoCard();
    return;
}

//display roster results
if ($roster = $package->noun()->rosterHTML($time)) {
    echo $roster;
} else {
    $n->notice('No roster data found for '.$cms->helper('strings')->date($time).'.');
}
