<?php
$package->cache_noStore();

$noun = $package->noun();
$package['fields.page_title'] = $noun->name().' roster rules management';

echo "<p><a href='".$noun->url('add', ['type'=>'roster-rules'], true)."'>Add revised roster rules</a></p>";

echo "<ul>";
foreach ($noun->allRosterRules() as $rules) {
    echo "<li>".$rules->url()->html()."</li>";
}
echo "</ul>";
