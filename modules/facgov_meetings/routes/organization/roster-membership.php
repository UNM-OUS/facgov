<?php
$package->cache_noStore();

$package['fields.page_title'] = $package->noun()->name() . ' membership management';
$form = new \Formward\Form('');
$form->addClass('compact-form');
$form['date'] = $cms->helper('forms')->field('date', '');
$form['date']->addTip('Setting a date here will persist for the rest of your session, even if you navigate away.');
$form['date']->default(time());

$s = $cms->helper('session');
$n = $cms->helper('notifications');

if ($time = $s->get('roster-membership-date')) {
    $form['date']->default($time);
}

if ($form->handle()) {
    $s->set('roster-membership-date', $form['date']->value());
}

echo "<ul><li><a href='" . $package->noun()->url('roster-membership-history') . "'>View all available historical roster terms by date.</a></li></ul>";
echo $form;

if ($roster = $package->noun()->rosterHTML($form['date']->value(), true)) {
    echo $roster;
} else {
    $n->notice('No roster data found for ' . $cms->helper('strings')->date($form['date']->value() . '.'));
}
