<?php
namespace Digraph\Modules\facgov_meetings;

class Hiatus extends \Digraph\DSO\Noun
{
    protected $_organization;

    public function infoCard()
    {
        return '<div class="notification notification-notice">'.
            '<strong>'.$this->name().'</strong><br>'.
            $this->body().
            '</div>';
    }

    public function searchIndexed()
    {
        return false;
    }

    public function name($verb=null)
    {
        $s = $this->cms()->helper('strings');
        return parent::name().
            ': '.$s->date($this->startTime()).
            ($this->endTime()?' - '.$s->date($this->endTime()):'');
    }

    public function startTime()
    {
        return $this['hiatus.start'];
    }

    public function endTime()
    {
        if (!$this['hiatus.end']) {
            return null;
        }
        return $this['hiatus.end'];
    }

    public function parentEdgeType($child)
    {
        if ($child['dso.type'] == 'organization') {
            return 'hiatus';
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

    public function formMap(string $action) : array
    {
        $s = $this->cms()->helper('strings');
        $map = parent::formMap($action);
        // hide unneeded fields
        $map['digraph_title'] = false;
        // alter body field
        $map['digraph_body']['label'] = 'Hiatus information (displayed to public)';
        $map['digraph_body']['weight'] = 110;
        $map['digraph_body']['class'] = 'digraph_content_default';
        $map['digraph_body']['tips'] = [
            'Markdown and basic BBCode are supported for formatting.'
        ];
        // effective date
        $map['hiatus_start'] = [
            'label' => 'Hiatus start date',
            'class' => 'date',
            'field' => 'hiatus.start',
            'weight' => 101,
            'required' => true
        ];
        // end date
        $map['hiatus_end'] = [
            'label' => 'Hiatus end date (optional)',
            'class' => 'date',
            'field' => 'hiatus.end',
            'weight' => 102,
            'required' => false,
            'tips' => ['End dates are not inclusive. For example, setting an end date of August 14 would cause the committee to leave hiatus at midnight the night of August 13.']
        ];
        return $map;
    }
}
