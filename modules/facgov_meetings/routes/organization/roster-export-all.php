<?php
$package->cache_noCache();

$form = new \Formward\Fields\Container('', 'date');
$form->method('get');
$form->tag = 'form';
$form->addClass('Form');
$form->addClass('compact-form');
$form['date'] = $cms->helper('forms')->field('date', '');
$form['date']->default(time());
$form['submit'] = new \Formward\SystemFields\Submit('Change date');

echo "<h2>Change date</h2>";
echo $form;
$n = $cms->helper('notifications');
if ($form['date']->value() > time()) {
    $n->warning('Roster information from future dates may be incomplete, and should be considered a draft at best. The information shown here is most likely only an automatically-generated projection based on currently-entered membership expirations and requirements.');
}

$s = $cms->helper('strings');
$package['fields.page_title'] = 'Roster browser';

roster($package->noun(), $form['date']->value(), $cms);

function roster($org, $time, $cms)
{
    echo "<div class='roster-section'>";
    echo "<h2>" . $org->link() . "</h2>";
    //display hiatus information
    if ($hiatus = $org->hiatus($time)) {
        echo $hiatus->infoCard();
        return;
    }
    //display roster results
    if ($roster = $org->rosterHTML($time)) {
        echo $roster;
    } else {
        $cms->helper('notifications')->notice('No roster data found for ' . $cms->helper('strings')->date($time) . '.');
    }
    echo "</div>";
    foreach ($org->subcommittees() as $s) {
        roster($s, $time, $cms);
    }
}

?>
<style>
.roster-section {
    page-break-after: always;
}
</style>
