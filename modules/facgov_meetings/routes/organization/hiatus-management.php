<?php
$package->cache_noStore();

$noun = $package->noun();
$package['fields.page_title'] = $noun->name().' hiatus management';

echo "<p><a href='".$noun->url('add', ['type'=>'hiatus'], true)."'>Add hiatus period</a></p>";

echo "<ul>";
foreach ($noun->allHiatusPeriods() as $rules) {
    echo "<li>".$rules->url()->html()."</li>";
}
echo "</ul>";
