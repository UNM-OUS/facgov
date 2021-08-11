<?php

namespace Digraph\Modules\facgov_meetings;

class Organization extends \Digraph\Modules\CoreTypes\Page
{
    protected $_organization = null;
    protected $_meetings = null;
    protected $_allRosterRules = null;
    protected $_allMembers = null;
    protected $_allHiatusPeriods = null;

    public function additionalSearchText()
    {
        return $this->rosterHTML();
    }

    public function actions($links)
    {
        $links = parent::actions($links);
        $links['reports'] = '!id/reports';
        return $links;
    }

    public function membershipTableHTML($data)
    {
        $s = $this->cms()->helper('strings');
        $out = '<div class="roster-membership digraph-block">';
        foreach ($data as $section => $rows) {
            $colNames = [];
            $sectionTable = '';
            foreach ($rows as $row) {
                $sectionTable .= PHP_EOL . '<tr>';
                if (count($row) > count($colNames)) {
                    $colNames = array_keys($row);
                }
                foreach ($row as $col => $value) {
                    $sectionTable .= PHP_EOL . '<td valign="top">' . $value . '</td>';
                }
                $sectionTable .= '</tr>';
            }
            $out .= PHP_EOL . '<h1 id="' . $this->sectionAnchor($section) . '">' . $section . '</h1><table>';
            $out .= PHP_EOL . '<tr><th>' . implode('</th><th>', $colNames) . '</th></tr>';
            $out .= PHP_EOL . $sectionTable;
            $out .= PHP_EOL . '</table>';
        }
        $out .= '</div>';
        return $out;
    }

    protected function sectionAnchor($section)
    {
        $name = 'roster-section-' . $section;
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\-_]+/i', '_', $name);
        return $name;
    }

    public function specialPositions($time = null)
    {
        //we need to specify a time to member->specialPositions, or it will return all of them
        if (!$time) {
            $time = time();
        }
        $positions = [
            'chair' => [],
            'vice-chair' => [],
            'president' => [],
            'president-elect' => [],
        ];
        foreach ($this->members($time) as $member) {
            foreach ($member->specialPositions($time) as $pos) {
                $positions[$pos['digraph.name']][] = [
                    'member' => $member,
                    'position' => $pos,
                ];
            }
        }
        return $positions;
    }

    public function specialPositionsHTML($time = null)
    {
        if ($positions = $this->specialPositions($time)) {
            $out = '<p>';
            foreach ($positions as $pos => $people) {
                if ($people) {
                    if ($pos == 'chair' && count($people) > 1) {
                        $pos = 'co-chair';
                    }
                    $out .= '<div class="special-membership-group"><strong>' . $pos . ': </strong>';
                    if (count($people) > 1) {
                        $out .= '<br>';
                    }
                    $out .= implode(', <br>', array_map(
                        function ($e) {
                            return '<span class="special-membership-position">' . $e['member']['digraph.name'] . ' <em>(' . $e['position']->dateString() . ')</em></span>';
                        },
                        $people
                    ));
                    $out .= '</div>';
                }
            }
            $out = str_replace('July 1, ', '', $out);
            $out .= '</p>';
            return $out;
        }
        return '';
    }

    public function rosterHTML($time = null, $admin = false)
    {
        if (!($data = $this->rosterTableData($time, $admin))) {
            return '';
        }
        return $this->specialPositionsHTML($time) .
            '<div class="membership-table-date incidental">Roster date: ' . $this->cms()->helper('strings')->date($time) . '</div>' .
            $this->membershipTableHTML($data);
    }

    public function rosterTableData($time = null, $admin = false)
    {
        $s = $this->cms()->helper('strings');
        //pull roster
        $roster = $this->roster($time);
        //for admins, ensure that there's a section called "hidden"
        if ($admin && !@$roster['hidden']) {
            $roster['hidden'] = [];
        }
        //build data
        $data = [];
        foreach ($roster as $section => $sectionMembers) {
            //only display section named "hidden" if admin is true
            //this is so a hidden section can hold people with special positions that don't belong
            //on the membership table proper
            if ($section == 'hidden' && !$admin) {
                continue;
            }
            //add section to data for table
            $data[$section] = [];
            foreach ($sectionMembers as $member) {
                $row = [
                    'member' => '<em>vacant</em>',
                    'type' => $member['type'],
                ];
                if ($member['member']) {
                    $row['member'] = $member['member']->membershipTableCell();
                    $row['start'] = preg_replace('/^July 1, /', '', $s->date($member['member']->startTime()));
                    $row['end'] = ' ';
                    if ($member['member'] && $member['member']->endTime()) {
                        $row['end'] = preg_replace('/^July 1, /', '', $s->date($member['member']->endTime()));
                    }
                }
                if ($admin) {
                    $row['netid'] = ' ';
                    $row['start'] = $row['start'] ?? ' ';
                    $row['end'] = $row['end'] ?? ' ';
                    $row[' '] = ' ';
                    if ($member['member']) {
                        $row['netid'] = $member['member']['netid'] ?? ' ';
                        $row[' '] = '<a href="' . $member['member']->url('edit', [], true) . '" class="row-button row-edit" title="edit member or dates">edit</a>';
                        $row[' '] .= '<a href="' . $member['member']->url('add', ['type' => 'special-position'], true) . '" class="row-button row-tag-person" title="add special position">add special position</a>';
                        foreach ($member['member']->specialPositions($time) as $pos) {
                            $row['type'] .= '<div><a href="' . $pos->url('edit', [], true) . '" title="click to edit this special position">' . $pos->name() . '</a></div>';
                        }
                        if ($notes = $member['member']->notes()) {
                            $row['type'] .= '<div class="notes">' . $notes . '</div>';
                        }
                    } else {
                        $row[' '] = '<a class="inline-button" href="' . $this->url('add', ['type' => 'roster-member', 'member_type' => $member['type'], 'member_section' => $section], true) . '" class="row-button row-create-person" title="add member here">add</a>';
                    }
                    $row[' '] = '<div style="white-space:nowrap;">' . $row[' '] . '</div>';
                }
                $data[$section][] = $row;
            }
            if ($admin) {
                $data[$section][] = [
                    'member' => '<a href="' . $this->url('add', ['type' => 'roster-member', 'member_section' => $section], true) . '">add override member</a>',
                ];
            }
        }
        return $data;
    }

    public function roster($time = null)
    {
        if ($time === null) {
            $time = time();
        }
        if (!($rules = $this->rosterRules($time))) {
            return [];
        }
        //build out skeleton roster with all the empty spaces allocated
        $roster = [];
        foreach ($rules['roster.members'] as $section => $sectionMembers) {
            $roster[$section] = [];
            foreach ($sectionMembers as $type => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $roster[$section][] = [
                        'type' => $type,
                        'member' => null,
                    ];
                }
            }
        }
        //find members from time
        foreach ($this->members($time) as $member) {
            //determine if section/type is remapped
            $section = $member['member.section'];
            $type = $member['member.type'];
            if ($remap = $rules["roster.renames.$section.$type"]) {
                list($section, $type) = $remap;
            }
            //ensure section exists
            if (!isset($roster[$section])) {
                $roster[$section] = [];
            }
            //try to place in an existing slot
            $placed = false;
            foreach ($roster[$section] as $i => $slot) {
                if ($slot['type'] == $type && !$slot['member']) {
                    $placed = true;
                    $roster[$section][$i]['member'] = $member;
                    break;
                }
            }
            //if not placed, create a slot
            if (!$placed) {
                $roster[$section][] = [
                    'type' => $type,
                    'member' => $member,
                ];
            }
        }
        //return finished roster
        return $roster;
    }

    public function allMembers()
    {
        if ($this->_allMembers === null) {
            $this->_allMembers = $this->cms()->helper('graph')
                ->children(
                    $this['dso.id'],
                    'roster-member',
                    1,
                    '${member.start} asc, ${member.end} desc, ${digraph.name} asc'
                );
            if (!$this->_allMembers) {
                $this->_allMembers = [];
            }
        }
        return $this->_allMembers;
    }

    public function members($time = null, $recurse = false)
    {
        //set time if null
        if ($time === null) {
            $time = time();
        }
        //get members from this organization
        $childIDs = $this->cms()->helper('graph')
            ->childIDs($this['dso.id'], 'roster-member');
        $childIDs = array_map(function ($e) {
            return "\"$e\"";
        }, $childIDs);
        $childIDs = '(' . implode(",", $childIDs) . ')';
        $search = $this->cms()->factory()->search();
        $search->where('${dso.id} in ' . $childIDs . ' AND ${member.start} <= :time AND (${member.end} is null OR ${member.end} = "" OR ${member.end} > :time)');
        $search->order('${member.start} asc, ${member.end} desc, ${digraph.name} asc');
        $members = $search->execute(['time' => $time]);
        //if recurse is true, also pull from subcommittees
        if ($recurse) {
            foreach ($this->subcommittees() as $sc) {
                foreach ($sc->members($time, true) as $member) {
                    $members[] = $member;
                }
            }
        }
        //return full list
        return $members;
    }

    public function rosterRules($time = null)
    {
        if ($time === null) {
            $time = time();
        }
        foreach ($this->allRosterRules() as $rule) {
            if ($rule->startTime() <= $time) {
                if (!$rule['roster.end'] || $rule->endTime() > $time) {
                    return $rule;
                } elseif ($rule['roster.end']) {
                    //need to return null as soon as we hit an invalid rule with an end date,
                    //because that means the most recent valid rule has ended
                    return null;
                }
            }
        }
        return null;
    }

    public function hiatus($time = null)
    {
        if ($time === null) {
            $time = time();
        }
        foreach ($this->allHiatusPeriods() as $hiatus) {
            if ($hiatus->startTime() <= $time) {
                if (!$hiatus['hiatus.end'] || $hiatus->endTime() > $time) {
                    return $hiatus;
                } elseif ($hiatus['hiatus.end']) {
                    //need to return null as soon as we hit an invalid hiatus with an end date,
                    //because that means the most recent valid hiatus has ended
                    return null;
                }
            }
        }
        return null;
    }

    /**
     * Get a list of all rost rules, sorted by start date descending.
     */
    public function allRosterRules()
    {
        if ($this->_allRosterRules === null) {
            $this->_allRosterRules = $this->cms()->helper('graph')
                ->children($this['dso.id'], 'roster-rules');
            usort(
                $this->_allRosterRules,
                function ($a, $b) {
                    if ($a['roster.start'] > $b['roster.start']) {
                        return -1;
                    } elseif ($a['roster.start'] < $b['roster.start']) {
                        return 1;
                    } elseif ($a['roster.end'] > $b['roster.end']) {
                        return -1;
                    } elseif ($a['roster.end'] < $b['roster.end']) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            );
            if (!$this->_allRosterRules) {
                $this->_allRosterRules = [];
            }
        }
        return $this->_allRosterRules;
    }

    /**
     * Get a list of all hiatus rules, sorted by start date descending.
     */
    public function allHiatusPeriods()
    {
        if ($this->_allHiatusPeriods === null) {
            $this->_allHiatusPeriods = $this->cms()->helper('graph')
                ->children($this['dso.id'], 'hiatus');
            usort(
                $this->_allHiatusPeriods,
                function ($a, $b) {
                    if ($a['hiatus.start'] > $b['hiatus.start']) {
                        return -1;
                    } elseif ($a['hiatus.start'] < $b['hiatus.start']) {
                        return 1;
                    } elseif ($a['hiatus.end'] > $b['hiatus.end']) {
                        return -1;
                    } elseif ($a['hiatus.end'] < $b['hiatus.end']) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            );
            if (!$this->_allHiatusPeriods) {
                $this->_allHiatusPeriods = [];
            }
        }
        return $this->_allHiatusPeriods;
    }

    public function organization()
    {
        if ($this->_organization === null) {
            $this->_organization = $this->cms()->helper('graph')->nearest($this['dso.id'], 'organization');
        }
        return $this->_organization;
    }

    public function parentEdgeType($child)
    {
        if ($child['dso.type'] == 'organization') {
            return 'organization';
        }
        return null;
    }

    public function meetings($filter = true)
    {
        if ($this->_meetings === null) {

            //all children
            $this->_meetings = $this->cms()
                ->helper('graph')
                ->children($this['dso.id'], 'meeting', 1);

            //sort by date (descending)
            usort(
                $this->_meetings,
                function ($a, $b) {
                    if ($a['meeting.start'] > $b['meeting.start']) {
                        return -1;
                    } elseif ($a['meeting.start'] < $b['meeting.start']) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            );
        }

        if ($filter) {
            //filter with MeetingHelper
            return $this->cms()
                ->helper('meetings')
                ->filterMeetings($this->_meetings);
        } else {
            return $this->_meetings;
        }
    }

    protected $_subcommittees = null;
    public function subcommittees()
    {
        if ($this->_subcommittees === null) {
            $this->_subcommittees = $this->factory->cms()
                ->helper('graph')
                ->children($this['dso.id'], 'organization', 1);
        }
        return $this->_subcommittees;
    }

    public function formMap(string $action): array
    {
        $s = $this->factory->cms()->helper('strings');
        $map = parent::formMap($action);
        // location
        $map['meetingdefaults_location'] = [
            'label' => 'Meeting defaults: Meeting location',
            'class' => 'text',
            'required' => true,
            'field' => 'meetingdefaults.meeting_location',
            'weight' => 902,
        ];
        // unlisted
        $map['meetingdefaults_unlisted'] = [
            'label' => 'Meeting defaults: Unlisted',
            'class' => 'checkbox',
            'required' => false,
            'field' => 'meetingdefaults.meeting_unlisted',
            'tips' => [
                'Unlisted meetings are not shown in schedules, but are visible to anyone with the link.',
            ],
            'weight' => 911,
        ];
        // closed to public
        $map['meetingdefaults_closed'] = [
            'label' => 'Meeting defaults: Closed to public',
            'class' => 'checkbox',
            'required' => false,
            'field' => 'meetingdefaults.meeting_closed',
            'tips' => [
                'Closed meetings and their attachments are hidden from the public until at least 24 hours after the meeting starts.',
                'Meeting and attachments become public once the meeting is visible.',
            ],
            'weight' => 912,
        ];
        // option to hide time
        $map['meetingdefaults_hide_time'] = [
            'label' => 'Meeting defaults: Hide time but show date',
            'class' => 'checkbox',
            'required' => false,
            'field' => 'meetingdefaults.meeting_hide_time',
            'weight' => 921,
        ];
        // option to hide location
        $map['meetingdefaults_hide_location'] = [
            'label' => 'Meeting defaults: Hide location',
            'class' => 'checkbox',
            'required' => false,
            'field' => 'meetingdefaults.meeting_hide_location',
            'weight' => 921,
        ];
        // hide title
        $map['digraph_title'] = false;
        // content label
        $map['digraph_body']['label'] = "Body content for organization home page";
        return $map;
    }
}
