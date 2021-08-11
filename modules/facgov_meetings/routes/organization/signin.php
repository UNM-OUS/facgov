<?php
$package->cache_noCache();

echo "<div class='noprint'>";
$form = new \Formward\Fields\Container('', 'date');
$form->method('get');
$form->tag = 'form';
$form->addClass('Form');
$form->addClass('compact-form');
$form['date'] = $cms->helper('forms')->field('date', '');
$form['date']->default(time());
$form['submit'] = new \Formward\SystemFields\Submit('Change date');
echo $form;
echo "</div>";

$date = $form['date']->value();
$package['fields.page_title'] = $package['fields.page_name'] = $package['fields']['page_title'] . ' sign-in sheet: ' . $date;

$members = $package->noun()->members(
    $form['date']->value()
);
usort(
    $members,
    function ($a, $b) {
        return strcmp($a->memberName(), $b->memberName());
    }
);

echo "<p><small>Roster is in alphabetical order by <em>first</em> name.</small></p>";
echo "<table style='width:100%;'>";
foreach ($members as $member) {
    echo "<tr>";
    echo "<td style='border-bottom:1px dotted #000 !important;height:0.3in;'>{$member->memberName()}</td>";
    echo "</tr>";
}
echo "</table>";

return;
