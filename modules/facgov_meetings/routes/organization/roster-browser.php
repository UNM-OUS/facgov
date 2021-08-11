<?php
if ($package['url.args.date_date_user']) {
    $url = $package->url();
    unset($url['args.date_date_user']);
    $package->redirect($url);
    return;
}
if ($package['url.args.date_date']) {
    $time = strtotime($package['url.args.date_date']);
    if ($time) {
        $url = $package->url();
        unset($url['args']);
        $url['args'] = [
            'date_date_actual' => $time
        ];
        $package->redirect($url);
        return;
    }
}

$form = new \Formward\Fields\Container('', 'date');
$form->method('get');
$form->tag = 'form';
$form->addClass('Form');
$form->addClass('compact-form');
$form['date'] = $cms->helper('forms')->field('date', '');
$form['date']->default(time());
$form['submit'] = new \Formward\SystemFields\Submit('Change date');

$n = $cms->helper('notifications');
$s = $cms->helper('strings');
$package['fields.page_title'] = 'Roster browser';

echo "<h2>" . $s->date($form['date']->value()) . ' ' . $package->noun()->name() . ' roster</h2>';
echo $form;
echo "<p class='incidental'><a href='" . $package->noun()->url('roster-browser-history') . "'>View all available historical roster terms by date</a></p>";
if ($form['date']->value() > time()) {
    $n->warning('Roster information from future dates may be incomplete, and should be considered a draft at best. The information shown here is most likely only an automatically-generated projection based on currently-entered membership expirations and requirements.');
}

//display hiatus information
if ($hiatus = $package->noun()->hiatus($form['date']->value())) {
    echo $hiatus->infoCard();
    return;
}

//display roster results
if ($roster = $package->noun()->rosterHTML($form['date']->value())) {
    echo $roster;
} else {
    $n->notice('No roster data found for ' . $cms->helper('strings')->date($form['date']->value() . '.'));
    if ($form['date']->value() < strtotime('1/1/2019')) {
        $n->notice('Old roster information may not be available on this site. Please contact the Office of the University Secretary if you need older roster information.');
    }
}
