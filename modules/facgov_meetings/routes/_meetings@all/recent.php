<?php
$package->cache_noStore();

$meetings = $cms->helper('meetings')->recent(50,false);
if (!$meetings) {
    $cms->helper('notifications')->printNotice('No meetings in the last 90 days');
    return;
}

foreach($meetings as $meeting){
    echo $meeting->infoCard();
}
