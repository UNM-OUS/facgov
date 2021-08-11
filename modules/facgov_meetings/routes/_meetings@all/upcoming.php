<?php
$package->cache_noStore();

$meetings = $cms->helper('meetings')->upcoming(50,false);
if (!$meetings) {
    $cms->helper('notifications')->printNotice('No meetings in the next 60 days');
    return;
}

foreach($meetings as $meeting){
    echo $meeting->infoCard();
}
