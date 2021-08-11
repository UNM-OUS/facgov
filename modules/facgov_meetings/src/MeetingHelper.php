<?php
namespace Digraph\Modules\facgov_meetings;

class MeetingHelper extends \Digraph\Helpers\AbstractHelper
{
    public function allOrgs($noun)
    {
        $orgs = array_filter($this->cms->helper('graph')->traverse(
            $noun['dso.id'],
            function ($id) {
                if ($noun = $this->cms->read($id)) {
                    if ($noun['dso.type'] == 'organization') {
                        return $noun;
                    }
                }
                return null;
            }
        ));
        usort(
            $orgs,
            function ($a, $b) {
                return strcmp($a->name(), $b->name());
            }
        );
        return $orgs;
    }

    public function tag_organization_full_list($noun)
    {
        $orgs = $this->allOrgs($noun);
        $out = '<ul>';
        foreach ($orgs as $org) {
            $out .= "<li>" . $org->link() . "</li>";
        }
        $out .= '</ul>';
        return $out;
    }

    public function tag_organization_chair_list($noun)
    {
        $orgs = $this->allOrgs($noun);
        $out = '<div class="roster-membership"><table>';
        $out .= '<tr><th>Council/Committee</th><th>Chair(s)</th></tr>';
        foreach ($orgs as $org) {
            $out .= "<tr>";
            $out .= "<td>" . $org->link() . "</td>";
            $out .= "<td>";
            if ($sps = $org->specialPositions()) {
                foreach ($sps as $name => $sp) {
                    if (stripos($name, 'chair') !== false && $name != 'vice-chair' && $name != 'vice chair') {
                        foreach ($sp as $s) {
                            $m = $s['member'];
                            $out .= '<div style="margin:0.5em 0;">';
                            $out .= $m->membershipTableCell();
                            $out .= '</div>';
                        }
                    }
                }
            }
            $out .= "</td>";
            $out .= "</tr>";
        }
        $out .= '</table></div>';
        return $out;
    }

    public function tag_organization_roster($noun)
    {
        $out = '';
        if ($sub = $noun->rosterRules()) {
            $out .= $noun->rosterHTML();
            $out .= "<p>View rosters on other dates in the " . $noun->url('roster-browser')->html() . "</p>";
        } elseif ($noun->allRosterRules()) {
            if ($hiatus = $noun->hiatus() && $last = $noun->rosterHTML($hiatus->startTime())) {
                $out .= $last;
                $out .= "<p>Past membership can be found in the " . $noun->url('roster-browser')->html() . "</p>";
            } else {
                $out .= "<p>No current roster is available. Past membership can be found in the " . $noun->url('roster-browser')->html() . "</p>";
            }
        }
        return $out;
    }

    public function tag_organization_meetings($noun, $all = null)
    {
        $meetings = $noun->meetings();
        $out = '';
        if (!$meetings) {
            $out .= '<em>No meetings found to display here</em>';
        }
        $count = 0;
        foreach ($meetings as $meeting) {
            // only display future meetings two weeks out on this page unless all is 'true'
            if ($all != 'true' && $meeting['meeting.start'] > time() + 86400 * 30) {
                continue;
            }
            //count and check count if all is not 'true'
            if ($all != 'true') {
                $count++;
                if ($count == 4) {
                    $out .= "<p><a href='" . $noun->url('meetings') . "'>View full meeting list</a></p>";
                    break;
                }
            }
            //display info card
            $out .= $meeting->infoCard();
        }
        return $out;
    }

    public function tag_organization_subcommittees($noun, $first = true)
    {
        $out = $first ? '' : '<li>' . $noun->link();
        // $out .= '<li>'.$noun->link();
        if ($sc = $noun->subcommittees()) {
            $out .= '<ul>';
            foreach ($sc as $s) {
                $out .= $this->tag_organization_subcommittees($s, false);
            }
            $out .= '</ul>';
        }
        $out .= $first ? '' : '</li>';
        return $out;
    }

    public function filterMeetings($meetings)
    {
        return array_filter(
            $meetings,
            function ($meeting) {
                return $meeting->listed();
            }
        );
    }

    public function upcoming($limit = 5, $filter = true)
    {
        $search = $this->cms->factory()->search();
        $search->where('${dso.type} = "meeting" AND ${meeting.start} > :start AND ${meeting.start} < :end');
        $search->order('${meeting.start} asc');
        $search->limit($limit);
        $output = $search->execute([
            'start' => time(),
            'end' => time() + (86400 * 60),
        ]);
        if ($filter) {
            $output = $this->filterMeetings($output);
        }
        return $output;
    }

    public function recent($limit = 5, $filter = true)
    {
        $search = $this->cms->factory()->search();
        $search->where('${dso.type} = "meeting" AND ${meeting.start} > :start AND ${meeting.start} < :end');
        $search->order('${meeting.start} desc');
        $search->limit($limit);
        $output = $search->execute([
            'start' => time() - (86400 * 90),
            'end' => time(),
        ]);
        if ($filter) {
            $output = $this->filterMeetings($output);
        }
        return $output;
    }

    public function resolutions()
    {
        $search = $this->cms->factory()->search();
        $search->where('${dso.type} = "meeting-files" AND ${meeting-files.type} = "resolution"');
        $output = $search->execute();
        usort(
            $output,
            function ($a, $b) {
                if ($a->meeting()['meeting.start'] > $b->meeting()['meeting.start']) {
                    return -1;
                } elseif ($a->meeting()['meeting.start'] < $b->meeting()['meeting.start']) {
                    return 1;
                } else {
                    return 0;
                }
            }
        );
        return $output;
    }
}
