<?php
$package['fields.page_title'] = $package->noun()->name().' full roster log';

$s = $cms->helper("strings");

echo "<p>Displaying all roster membership records with a start date before the last time this report was generated: <strong>".$s->datetime(time())."</strong></p>";

echo "<table>";
echo "<tr><th>Member</th><th>Section/Type</th><th>Start</th><th>End</th></tr>";
foreach (array_reverse($package->noun()->allMembers()) as $m) {
    if ($m['member.section'] == 'hidden') {
        continue;
    }
    if ($m->startTime() > time()) {
        continue;
    }
    echo "<tr>";
    echo "<td>".$m->membershipTableCell();
    foreach ($m->specialPositions() as $pos) {
        echo '<div>'.$pos->name().'</div>';
    }
    echo "</td>";
    echo "<td>".$m['member.section'].': '.$m['member.type']."</td>";
    echo "<td>".$s->date($m->startTime())."</td>";
    if ($m->endTime()) {
        echo "<td>".$s->date($m->endTime())."</td>";
    } else {
        echo "<td> </td>";
    }
    echo "</tr>";
}
echo "</table>";
