<?php
use Digraph\Modules\facgov_meetings\RosterMember;

$package['fields.page_title'] = $package->noun()->name() . ' term timeline';
$n = $cms->helper('notifications');
$org = $package->noun();

$members = $package->noun()->members();
echo "<table style='max-width:100%;'>";
echo "<tr><th>Current</th><th>Previous terms</th></tr>";
foreach ($members as $member) {
    echo "<tr>";
    // print current position
    echo "<td valign='top'>";
    currentPosition($member);
    echo "</td>";
    // find/print past positions
    echo "<td valign='top'>";
    pastPositions($member);
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

function termString(RosterMember $member)
{
    $s = $member->cms()->helper('strings');
    $start = preg_replace('/^July 1, /', '', $s->date($member->startTime()));
    if ($member->endTime()) {
        $end = preg_replace('/^July 1, /', '', $s->date($member->endTime()));
    } else {
        $end = '';
    }
    return "$start &ndash; $end";
}

function currentPosition(RosterMember $member)
{
    echo $member->membershipTableCell();
    echo "<p>" . termString($member) . ':<br>' . $member['member.section'] . ':<br>' . $member['member.type'] . '</p>';
}

function pastPositions(RosterMember $member)
{
    $cms = $member->cms();
    $search = $cms->factory()->search();
    $args = [
        'name' => $member['digraph.name'],
        'end' => $member->startTime() + 1,
        'id' => $member['dso.id'],
    ];
    $where = '${dso.id} in ("' . implode('","', $cms->helper('graph')->childIDs($member->organization()['dso.id'], 'roster-member')) . '")';
    $where .= ' AND ${dso.id} <> :id';
    $where .= ' AND ${member.end} < :end';
    if ($member['netid']) {
        $args['netid'] = $member['netid'];
        $where .= ' AND (${netid} = :netid OR ${digraph.name} = :name)';
    } else {
        $where .= ' AND ${digraph.name} = :name';
    }
    $search->where($where);
    $search->order('${member.start} desc');
    $prevpos = $member;
    foreach ($search->execute($args) as $pos) {
        if ($prevpos['member.start'] > $pos['member.end']) {
            echo "<div class='notification incidental' style='padding:0.25em;'>break";
            if ($prevpos['member.start'] - $pos['member.end'] < 86400 * 365) {
                echo " < 1 year";
            }
            echo "</div>";
        }
        $class = 'notice';
        if ($pos['member.section'] != $member['member.section'] || $pos['member.type'] != $member['member.type']) {
            $class = 'warning';
        }
        echo "<div class='notification incidental notification-$class' style='margin:0;padding:0.25em;'>";
        echo "<a href='" . $pos->url() . "'>";
        echo termString($pos);
        echo "</a>";
        if ($pos['member.section'] != $member['member.section']) {
            echo "<div>different section: " . $pos['member.section'] . "</div>";
        }
        if ($pos['member.type'] != $member['member.type']) {
            echo "<div>different type: " . $pos['member.type'] . "</div>";
        }
        echo "</div>";
        $prevpos = $pos;
    }
}

function upcomingPositions(RosterMember $member)
{

}
