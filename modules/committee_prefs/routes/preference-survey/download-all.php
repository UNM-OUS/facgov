<?php

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

$package->cache_noStore();

/** @var Digraph\Modules\committee_prefs\Forms\PreferenceSurvey */
$survey = $package->noun();
$writer = WriterEntityFactory::createXLSXWriter();
$writer->openToBrowser($package->noun()->name() . ' all responses.xlsx');
$package->makeMediaFile($package->noun()->name() . ' all responses.xlsx');

$headers = [
    'Name',
    'Tenured',
    'College',
    'Department',
    'Title',
    'Max Committees'
];
$writer->addRow(
    WriterEntityFactory::createRowFromArray($headers)
);

foreach ($survey->allSignups() as $signup) {
    if (!$signup->complete()) {
        continue;
    }
    $row = [
        $signup->contactInfo()->name(),
        $signup['contact.tenure'] ? 'Yes' : '',
        $signup['contact.college'],
        $signup['contact.department'],
        $signup['contact.title'],
        $signup['preferences.max']
    ];
    foreach ($signup['preferences.order'] ?? [] as $id) {
        $row[] = $cms->read($id, false)->name();
    }
    $writer->addRow(
        WriterEntityFactory::createRowFromArray($row)
    );
}

$writer->close();
