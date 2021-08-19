<?php

use Digraph\Forms\Fields\Noun;
use Digraph\Modules\ous_event_management\Signup;

$org = $package->url()->getData();

$form = $cms->helper('forms')->form('');
$form['org'] = new Noun('Committee');
$form['org']->limitTypes(['organization']);
$form['org']->required(true);
$form['org']->default($org);

echo $form;
if ($form->handle()) {
    $url = $package->url();
    $url->setData($form['org']->value());
    $package->redirect($url);
    return;
}

if (!$org) {
    return;
}

// display results from cache if possible
$cacheID = md5($package->noun()['dso.id'] . 'reports' . $org);
$cache = $cms->cache();
if ($cache->hasItem($cacheID)) {
    echo $cache->getItem($cacheID)->get();
    return;
}

// otherwise generate results
ob_start();
$survey = $package->noun();
$signups = $survey->allSignups();
$signups = array_filter($signups, function (Signup $s) use ($org) {
    if (!$s->complete()) {
        return false;
    }
    return in_array($org, $s['preferences.order']);
});
usort($signups, function ($a, $b) use ($org) {
    $aPos = array_search($org, array_values($a['preferences.order']));
    $bPos = array_search($org, array_values($b['preferences.order']));
    $s = $aPos - $bPos;
    if ($s == 0) {
        $s = $a['dso.created.date'] - $b['dso.created.date'];
    }
    return $s;
});

echo "<table class='incidental'>";
echo "<tr><th>Person</th>";
echo "<th colspan='5'>Top 5 choices</th>";
echo "</tr>";
foreach ($signups as $s) {
    echo "<tr>";
    echo "<td style='border:1px dotted #999;'>";
    $s->contactInfo()->body_complete();
    echo "</td>";
    foreach (array_slice($s['preferences.order'], 0, 5) as $p) {
        echo $p == $org ? '<td style="font-weight:bold;vertical-align:top;">' : '<td style="vertical-align:top;">';
        $p = $cms->read($p, false);
        echo $p->name();
        echo "</td>";
    }
    echo "</tr>";
}
echo "</table>";

//save output to cache
$citem = $cache->getItem($cacheID);
$citem->set(ob_get_contents());
$citem->expiresAfter(3600*2);
$cache->save($citem);
ob_end_flush();
