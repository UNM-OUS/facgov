<?php
//TODO: take time from url parameter
$time = time();
//TODO: actually secure details
$details = true;

$package->makeMediaFile(
    $package->noun()->name()
    .date('Y-m-d', $time)
    .($details?'_detail':'')
    .'_roster.json'
);

$out = [];

//loop through roster
if ($roster = $package->noun()->roster($time)) {
    foreach ($roster as $cat => $members) {
        foreach ($members as $m) {
            if (!$m['member']) {
                continue;
            }
            //basic info
            $member = [
                'name' => $m['member']['digraph.name'],
                'category' => $cat,
                'type' => $m['type'],
                'start' => $m['member']->startTime(),
                'end' => $m['member']->endTime(),
                'positions' => array_map(
                    function ($e) {
                        return $e['digraph.name'];
                    },
                    array_values($m['member']->specialPositions($time))
                ),
                'info' => trim(
                    preg_replace(
                        '/[-0-9a-z.+_]+@[-0-9a-z.+_]+[a-z]/i',
                        '',
                        $m['member']['digraph.body.text']
                    )
                )
            ];
            //sensitive stuff requires an API key
            if ($details) {
                $member['info'] = $m['member']['digraph.body.text'];
                $member['netid'] = $m['member']['netid'];
            }
            //put into output
            $out[] = $member;
        }
    }
}

echo json_encode($out);
