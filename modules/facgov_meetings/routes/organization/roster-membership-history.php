<?php
$package['fields.page_title'] = $package->noun()->name().' full roster log';

$s = $cms->helper("strings");

echo "<table>";
echo "<tr><th>Member</th><th>Section/Type</th><th>Start</th><th>End</th><th> </th></tr>";
foreach (array_reverse($package->noun()->allMembers()) as $m) {
    echo "<tr>";
    echo "<td>".$m->membershipTableCell();
    foreach ($m->specialPositions() as $pos) {
        echo '<div><a href="'.$pos->url('edit', [], true).'" title="click to edit this special position">'.$pos->name().'</a></div>';
    }
    echo "</td>";
    echo "<td>".$m['member.section'].': '.$m['member.type']."</td>";
    echo "<td>".$s->date($m->startTime())."</td>";
    if ($m->endTime()) {
        echo "<td>".$s->date($m->endTime())."</td>";
    } else {
        echo "<td> </td>";
    }
    echo "<td style='white-space:nowrap;'>";
    echo '<a href="'.$m->url('edit', [], true).'" class="row-button row-edit" title="edit member or dates">edit</a>';
    echo '<a href="'.$m->url('add', ['type'=>'special-position'], true).'" class="row-button row-tag-person" title="add special position">add special position</a>';
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
