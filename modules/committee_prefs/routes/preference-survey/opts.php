<?php

use Digraph\Modules\facgov_meetings\Organization;
use Formward\Fields\Ordering;

$package->cache_noStore();
/** @var Digraph\Forms\FormHelper */
$forms = $cms->helper('forms');
/** @var Digraph\Modules\committee_prefs\Forms\PreferenceSurvey */
$survey = $package->noun();
$options = $survey->options();

/**
 * Form for adding options to the list
 */
$addForm = $forms->form('Add options');
$addForm['noun'] = $forms->field('noun', 'Organization');
$addForm['noun']->limitTypes(['organization']);
$addForm['noun']->required(true);
$addForm['recurse'] = $forms->field('checkbox', 'Recursively add all child organizations (for example adding all faculty senate subcommittees)');
$addForm['recurse']->default(false);
$addForm['exclude'] = $forms->field('checkbox', 'Exclude specified organization (check to only include subcommittees, for example adding all faculty senate committees but not the senate itself)');
$addForm['exclude']->default(false);
if ($addForm->handle()) {
    $org = $cms->read($addForm['noun']->value());
    if (!$addForm['exclude']->value()) {
        $options[$org['dso.id']] = $org;
    }
    if ($addForm['recurse']->value()) {
        foreach ($cms->helper('graph')->children($org['dso.id'], 'organization', -1) as $child) {
            if ($child instanceof Organization) {
                $options[$child['dso.id']] = $child;
            }
        }
    }
    // set updated options
    $survey->options($options);
    $package->redirect($package->url());
    $cms->helper('notifications')->flashConfirmation('Added options');
    return;
}

/**
 * Form for controlling ordering and deleting
 */
$orderForm = $forms->form('Reorder or remove options');
$orderForm['order'] = new Ordering('');
$orderForm['order']->allowDeletion(true);
$orderForm['order']->opts(array_map(
    function($e) {
        return $e->name();
    },
    $options
));
if ($orderForm->handle()) {
    $survey->options($orderForm['order']->value());
    $package->redirect($package->url());
    $cms->helper('notifications')->flashConfirmation('Option list changes saved');
    return;
}

/**
 * Echo both forms last
 */
if ($options) {
    echo $orderForm;
    echo "<hr>";
}
echo $addForm;
