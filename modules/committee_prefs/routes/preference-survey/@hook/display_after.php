<?php
$survey = $package->noun();
$date = $cms->helper('strings')->date($package['noun.appointmentstart']);

echo "<h2>More information about committees</h2>";
echo "<p>This survey is used to help fill appointments for terms beginning $date. See below for more information about committees, as well as links to their projected rosters for the start of the terms being appointed.</p>";
echo "<ul>";
foreach ($survey->options() as $org) {
    echo "<li>";
    echo "<strong>" . $org->link() . "</strong><br>";
    $url = $org->url('roster-browser', ['date_date_actual' => $survey['appointmentstart']]);
    $vacancies = $survey->vacancyCount($org);
    echo "<a href='$url' class='incidental'>approximately $vacancies faculty vacanc".($vacancies==1?'y':'ies')." expected</a>";
    echo "</li>";
}
echo "</ul>";
