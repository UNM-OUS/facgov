<?php
$committees = [
    1 => 'kunm-radio-board/',
    2 => 'aft/',
    3 => 'cog/',
    6 => 'student-publications-board/',
    8 => 'senate/academic/admissions-and-registration/',
    9 => 'senate/athletic/',
    11 => 'senate/business/budget/',
    12 => 'senate/business/cdac/',
    13 => 'senate/business/ituc/',
    14 => 'senate/academic/curricula/',
    15 => 'senate/ops/faculty-ethics-and-advisory/',
    16 => 'senate/business/faculty-and-staff-benefits/',
    17 => 'senate/business/governmental-relations/',
    18 => 'senate/academic/graduate-professional/',
    19 => 'senate/academic/graduate-professional/honorary-degree/',
    21 => 'senate/rcw/library/',
    22 => 'senate/rcw/rac/',
    23 => 'senate/rcw/rpc/',
    25 => 'senate/academic/teaching-enhancement/',
    26 => 'senate/academic/undergraduate/',
    28 => 'senate/rcw/university-press/',
    31 => 'senate/ops/policy/',
    32 => 'senate/academic/',
    33 => 'senate/business/',
    35 => 'senate/rcw/',
    36 => 'senate/ops/',
    37 => 'senate/hsc/',
    38 => 'senate/hsc/executive/',
    39 => 'senate/hsc/policy/',
    40 => 'senate/gened/'
];

$comm = @$_GET['comm'];
if (!isset($committees[$comm])) {
    header("HTTP/1.0 404 Not Found");
    exit("Council/committee not found");
}

header("Location: https://facgov.unm.edu/".$committees[$comm]);
