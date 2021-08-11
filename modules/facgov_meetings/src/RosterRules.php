<?php

namespace Digraph\Modules\facgov_meetings;

class RosterRules extends \Digraph\DSO\Noun
{
    protected $_organization;

    public function searchIndexed()
    {
        return false;
    }

    public function startTime()
    {
        return $this['roster.start'];
    }

    public function endTime()
    {
        return $this['roster.end'];
    }

    public function body()
    {
        if (!$this->organization()) {
            return '[error: cannot generate membership table without a parent organization]';
        }
        $membership = [];
        $total = 0;
        foreach ($this['roster.members'] as $section => $types) {
            $sectionTotal = 0;
            $membership[$section] = [];
            foreach ($types as $type => $count) {
                $membership[$section][] = [
                    'type' => $type,
                    'count' => $count
                ];
                $sectionTotal += $count;
                $total += $count;
            }
            $membership["$section ($sectionTotal)"] = $membership[$section];
            unset($membership[$section]);
        }
        return "<h2>Total members: $total</h2>".
            $this->organization()->memberShipTableHTML($membership);
    }

    public function name($verb = null)
    {
        $s = $this->cms()->helper('strings');
        if ($this->endTime()) {
            $name = $s->date($this->startTime()) . ' through ' . $s->date($this->endTime());
            $name .= ' roster rules';
        } else {
            $name = 'Roster rules beginning ' . $s->date($this->startTime());
        }
        return $name;
    }

    public function parentEdgeType($child)
    {
        if ($child['dso.type'] == 'organization') {
            return 'roster-rules';
        }
        return null;
    }

    public function organization()
    {
        if ($this->_organization === null) {
            $this->_organization = $this->cms()->helper('graph')->nearest($this['dso.id'], 'organization');
        }
        return $this->_organization;
    }

    public function formMap(string $action): array
    {
        $s = $this->cms()->helper('strings');
        $map = parent::formMap($action);
        // hide unneeded fields
        $map['digraph_name'] = false;
        $map['digraph_title'] = false;
        // edit default fields
        $map['digraph_body']['label'] = 'Membership requirement notes';
        $map['digraph_body']['class'] = 'digraph_content_default';
        $map['digraph_body']['tips'][] = 'Visible to editors on membership management page.';
        // effective date
        $map['roster_start'] = [
            'label' => 'Effective date',
            'class' => 'date',
            'field' => 'roster.start',
            'weight' => 1,
            'required' => true
        ];
        // end date
        $map['roster_end'] = [
            'label' => 'End date (optional)',
            'class' => 'date',
            'field' => 'roster.end',
            'weight' => 2,
            'required' => false,
            'tips' => [
                'Generally this can be left blank, it\'s only really necessary if a committee is being dissolved or suspended.',
                'End dates are not inclusive. For example, setting an end date of August 14 would cause the roster rules to become not current at midnight the night of August 13.'
            ]
        ];
        // member rules configuration is set via a YAML formatted string
        $map['roster_members'] = [
            'label' => 'Membership sections',
            'class' => 'yaml',
            'field' => 'roster.members',
            'weight' => 10,
            'required' => true
        ];
        // member rules configuration is set via a YAML formatted string
        $map['roster_renames'] = [
            'label' => 'Membership section renames',
            'class' => 'yaml',
            'field' => 'roster.renames',
            'weight' => 11,
            'required' => false,
            'tips' => [
                'Use this field when a section/type has been renamed, to define how old members should be mapped into these rules.',
                'Structure is roughly the same as membership sections. Structure the old section/type the same way it was in previous rules, and set the value to an array of the new section/type.'
            ]
        ];
        //set defaults from previous rules for this organization
        if ($action == 'add') {
            if ($parent = $this->cms()->package()->noun()) {
                if ($current = $parent->rosterRules()) {
                    $map['roster_members']['default'] = $current['roster.members'];
                    $map['digraph_body']['default'] = $current['digraph.body'];
                }
            }
        }
        //return
        return $map;
    }
}
