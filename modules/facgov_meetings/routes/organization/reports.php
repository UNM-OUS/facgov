<?php
$package->cache_noCache();
$id = $package['noun.dso.id'];

$links = [
    'Roster data' => [
        [
            "$id/roster-membership",
            'Manage roster',
        ],
        [
            "$id/roster-export",
            'Export roster in various ways',
        ],
        [
            "$id/vacancies-export",
            'Export roster vacancies',
        ],
        [
            "$id/terms-timeline",
            'Display the past terms of all roster members',
        ],
        [
            "$id/signin",
            'Printable signin sheet',
        ],
        [
            "$id/roster-rules",
            'Manage the rules that define the available membership slots',
        ],
    ],
    'Organization settings' => [
        [
            "$id/hiatus-management",
            'Manage committee hiatus periods',
        ],
    ]
];

foreach ($links as $section => $sl) {
    $shtml = '';
    foreach ($sl as $l) {
        $shtml .= "<dl>";
        if ($url = $cms->helper('urls')->parse($l[0])) {
            if ($cms->helper('permissions')->checkUrl($url)) {
                $shtml .= "<dt>" . $url->html() . "</dt>";
                $shtml .= "<dd>" . $l[1] . "</dd>";
            }
        }
        $shtml .= "</dl>";
    }
    if ($shtml != '<dl></dl>') {
        echo "<h2>$section</h2>" . $shtml;
    }
}
