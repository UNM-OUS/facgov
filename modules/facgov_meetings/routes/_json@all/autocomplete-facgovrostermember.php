<?php
$package->cache_noCache();
$package['response.cache.immutable'] = true;
$package->makeMediaFile('results.json');
$q = $package['url.args.term'];
$base = $cms->config['url.base'];
if (substr($q, 0, strlen($base)) == $base) {
    $q = substr($q, strlen($base));
}

$results = [];

// look for exact matches
foreach ($cms->locate($q) as $n) {
    $results[$n['dso.id']] = $n;
}

// set up basic search
$search = $cms->factory()->search();
$search->limit(20);
$search->order('${dso.modified.date} desc');

// look for exact name/title matches
$search->where('${digraph.name} = :q OR ${digraph.title} = :q');
runsearch($search, ['q' => "$q"], $results);

// look for leading name/title matches
$search->where('${digraph.name} like :q OR ${digraph.title} like :q');
runsearch($search, ['q' => "$q%"], $results);

// look for partial name/title matches
$search->where('${digraph.name} like :q OR ${digraph.title} like :q');
runsearch($search, ['q' => "%$q%"], $results);

// build final JSON output
$results = array_values(array_map(
    function ($n) {
        $desc = preg_replace("/[\r\n]+/", '<br>', $n['digraph.body.text']);
        if ($n['netid']) {
            $desc = "<strong>".$n['netid']."</strong><br/>$desc";
        }
        return [
            'value' => trim($n['digraph.name']),
            'label' => $n['digraph.name'],
            'desc' => $desc,
            'field_netid' => $n['netid'],
            'field_body' => $n['digraph.body.text'],
        ];
    },
    $results
));
array_unshift($results,[
    'value' => $q,
    'label' => $q,
    'desc' => "<em>Enter without autocompleting</em>"
]);
echo json_encode($results);

// function for adding to results
function runSearch($search, $args, &$results)
{
    $search->where(
        '${dso.type} = "roster-member" AND (' . $search->where() . ')'
    );
    foreach ($search->execute($args) as $n) {
        if (!isset($results[$n['digraph.name']])) {
            $results[$n['digraph.name']] = $n;
        }
    }
}
