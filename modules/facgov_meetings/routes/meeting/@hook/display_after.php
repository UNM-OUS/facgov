<?php
$noun = $package->noun();
$s = $cms->helper('strings');

if ($noun->meetingFiles()) {
    foreach ($noun->meetingFiles() as $file) {
        echo $file->infoCard();
    }
}

if ($roster = $noun->rosterHTML()) {
    echo "<h2>Roster</h2>";
    $url = $noun->organization()->url('roster-browser',['date_date'=>date('Y-m-d',$noun['meeting.start'])]);
    echo "<p><a href='$url'>View ".$noun->organization()->name()." roster for ".$s->date($noun['meeting.start'])."</a></p>";
}
