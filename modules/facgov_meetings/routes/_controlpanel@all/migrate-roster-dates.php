<?php
$package->cache_noStore();
ini_set('max_execution_time', '0');

$search = $cms->factory()->search();
$search->where('${dso.type} = "roster-rules"');
$search->limit(10);
$offset = 0;
while ($result = $search->execute()) {
    foreach ($result as $rules) {
        if (strpos($rules['roster.start'].$rules['roster.end'],'-')) {
            echo "<div>changing ".$rules['dso.id']."</div>";
            $rules['roster.start'] = strtotime($rules['roster.start']);
            if ($rules['roster.end']) {
                $rules['roster.end'] = strtotime($rules['roster.end']);
            }
            $rules->update();
        }
    }
    $offset = $offset + 10;
    $search->offset($offset);
}
