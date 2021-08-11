<?php
namespace Digraph\Modules\facgov_meetings;

class MeetingFiles extends \Digraph\DSO\Noun
{
    const FILESTORE = true;
    
    protected $_meeting = false;
    protected $_organization = false;

    public function fileMetaCardMeta()
    {
        return [
            'time'
        ];
    }

    public function infoCard($context = false)
    {
        $out = ['<div class="digraph-card meeting-files">'];
        //extra context
        if ($context && $meeting = $this->meeting()) {
            $out[] = '<div class="title">'.$meeting->name().'</div>';
            //show date/time
            $out[] = '<div class="date">'.$meeting->dateTimeString().'</div>';
        }
        //name
        $out[] = '<div class="title">'.$this->name().'</div>';
        //more information link
        $out[] = '<a class="link" href="'.$this->url().'">more information</a>';
        $out[] = '</div>';
        return implode(PHP_EOL, $out);
    }

    public function name($verb=null)
    {
        if ($this['digraph.name']) {
            return $this['digraph.name'];
        }
        switch ($this['meeting-files.type']) {
            case "agenda":
                return "Agenda";
            case "minutes":
                return "Minutes/Notes";
            case "resolution":
                return "Resolution";
            default:
                return "Attached files";
        }
    }

    public function title($verb=null)
    {
        if ($this['digraph.name']) {
            return $this['digraph.name'];
        }
        if ($this->name() == "Resolution") {
            return "Resolution";
        }
        if ($this->meeting()) {
            return $this->meeting()->name().': '.$this->name();
        } else {
            return $this->name();
        }
    }

    public function parentEdgeType($child)
    {
        if ($child['dso.type'] == 'meeting') {
            return 'meeting-files';
        }
        return null;
    }

    public function meeting()
    {
        if ($this->_meeting === false) {
            $this->_meeting = $this->cms()->helper('graph')->nearest($this['dso.id'], 'meeting');
        }
        return $this->_meeting;
    }

    public function organization()
    {
        if ($this->_organization === false) {
            $this->_organization = $this->cms()->helper('graph')->nearest($this['dso.id'], 'organization');
        }
        return $this->_organization;
    }

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        //modifications to default fields
        $map['digraph_name']['required'] = false;
        $map['digraph_name']['tips'][] = 'Leave blank unless you need to override default naming for some reason';
        $map['digraph_title'] = false;
        $map['digraph_slug'] = false;
        //type field
        $map['meeting_files_type'] = [
            'weight' => 0,
            'label' => 'Type',
            'class' => 'select',
            'options' => [
                'agenda'=>'Agenda',
                'minutes'=>'Minutes/Notes',
                'resolution'=>'Resolution',
                'other'=>'Other'
            ],
            'required' => true,
            'field' => 'meeting-files.type'
        ];
        //file fields
        $map['files_main'] = [
            'weight' => 250,
            'label' => 'Main files',
            'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldMulti',
            'required' => false,
            'extraConstructArgs' => ['main']
        ];
        $map['files_supporting'] = [
            'weight' => 251,
            'label' => 'Supporting files',
            'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldMulti',
            'required' => false,
            'extraConstructArgs' => ['supporting']
        ];
        return $map;
    }
}
