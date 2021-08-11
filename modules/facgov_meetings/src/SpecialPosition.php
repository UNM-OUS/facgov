<?php
namespace Digraph\Modules\facgov_meetings;

class SpecialPosition extends \Digraph\DSO\Noun
{
    protected $_member;

    public function hook_postEditUrl()
    {
        if ($this->member()) {
            return $this->member()->hook_postEditUrl();
        }
        return $this->url('edit', null, true)->string();
    }

    public function hook_postAddUrl()
    {
        return $this->hook_postEditUrl();
    }

    public function dateString()
    {
        $s = $this->cms()->helper('strings');
        $out = $s->date($this->startTime());
        if ($this->endTime()) {
            $out .= ' to '.$s->date($this->endTime());
        }
        return $out;
    }

    public function name($verb=null)
    {
        return strtolower(parent::name($verb)).': '.$this->dateString();
    }

    public function searchIndexed()
    {
        return false;
    }

    public function startTime()
    {
        return $this['position.start'];
    }

    public function endTime()
    {
        return $this['position.end'];
    }

    public function parentEdgeType($child)
    {
        if ($child['dso.type'] == 'roster-member') {
            return 'special-position';
        }
        return null;
    }

    public function member()
    {
        if ($this->_member === null) {
            $this->_member = $this->cms()->helper('graph')->nearest($this['dso.id'], 'roster-member');
        }
        return $this->_member;
    }

    public function formMap(string $action) : array
    {
        $s = $this->cms()->helper('strings');
        $map = parent::formMap($action);
        // hide unneeded fields
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
        // tips regarding name
        $map['digraph_name']['tips'][] = 'Convention is for this field to be all lower-case.';
        $map['digraph_name']['tips'][] = 'If there are multiple chairs, just enter "chair" here. "Co-chair" will be automatically used on the front-end whenever it is appropriate.';
        $map['digraph_name']['tips'][] = 'The most important guideline for this field is to be consistent within any single committee. Always make an effort to spell and capitalize special position names the same way.';
        // effective date
        $map['position_start'] = [
            'label' => 'Position start date',
            'class' => 'date',
            'field' => 'position.start',
            'weight' => 101,
            'required' => true
        ];
        // end date
        $map['position_end'] = [
            'label' => 'Position end date (optional)',
            'class' => 'date',
            'field' => 'position.end',
            'weight' => 102,
            'required' => false,
            'tips' => ['End dates are not inclusive. For example, setting an end date of August 14 would cause the position to disappear from the roster at midnight the night of August 13.']
        ];
        return $map;
    }
}
