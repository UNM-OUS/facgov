<?php
$package->cache_noCache();
$noun = $package->noun();
$meetings = $noun->meetings(false);
if (!$meetings) {
    return;
}
$count = 0;
$currentYear = date('Y');
foreach ($meetings as $meeting) {
    $year = date('Y', $meeting['meeting.start']);
    if ($year != $currentYear) {
        $currentYear = $year;
        echo "<h3>$currentYear</h3>";
    }
    echo $meeting->infoCard();
    $count++;
}
