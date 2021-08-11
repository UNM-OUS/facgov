<?php
namespace Digraph\Modules\facgov_meetings;

class Meeting extends \Digraph\DSO\Noun
{
    protected $_organization;
    protected $_meetingFiles;

    public function breadcrumbName(string $verb): ?string
    {
        if ($verb == 'display') {
            return $this->dateString();
        }
        return null;
    }

    public function rosterHTML($admin = false)
    {
        if ($org = $this->organization()) {
            return $org->rosterHTML($this['meeting.start'], $admin);
        }
        return '';
    }

    public function roster()
    {
        if ($org = $this->organization()) {
            return $org->roster($this['meeting.start']);
        }
        return [];
    }

    public function agenda()
    {
        $files = $this->meetingFiles();
    }

    public function meetingFiles()
    {
        if ($this->_meetingFiles === null) {
            $this->_meetingFiles = $this->sortMeetingFiles(
                $this->cms()
                    ->helper('graph')
                    ->children($this['dso.id'], 'meeting-files', 1)
            );
        }
        return $this->_meetingFiles;
    }

    protected function sortMeetingFiles($arr)
    {
        usort(
            $arr,
            function ($a, $b) {
                $basis = [];
                //sort by type
                $types = [
                    'resolution' => 1,
                    'minutes' => 2,
                    'agenda' => 3,
                    'other' => 4
                ];
                $basis[] = [@$types[$a['meeting-files.type']],@$types[$b['meeting-files.type']]];
                //sort by name
                $basis[] = [$a->name(),$b->name()];
                //sort by date posted
                $basis[] = [$a['dso.created.date'],$b['dso.created.date']];
                //do sort
                foreach ($basis as $c) {
                    if ($c[0] > $c[1]) {
                        return 1;
                    } elseif ($c[0] < $c[1]) {
                        return -1;
                    }
                }
                //return 0 by default
                return 0;
            }
        );
        return $arr;
    }

    public function listed()
    {
        //force unlisted via unlisted flag
        if ($this['meeting.unlisted']) {
            return false;
        }
        //closed meetings are unlisted until 24 hours after start
        if ($this['meeting.closed']) {
            return time() > ($this['meeting.start']+86400);
        }
        //listed by default
        return true;
    }

    public function infoCard($partial = false, $showOrganization = false)
    {
        $out = ['<div class="digraph-card meeting'.($partial?' partial':'').($this->listed()?'':' unlisted').'">'];
        if ($showOrganization && $this->organization() && strpos($this->name(), $this->organization()->name()) === false) {
            $out[] = '<div class="title organization">'.$this->organization()->name().'</div>';
        }
        $out[] = '<div class="title">'.$this->name().'</div>';
        //show date/time
        $out[] = '<div class="date">'.$this->dateTimeString().'</div>';
        //show location
        $out[] = '<div class="date">'.$this['meeting.location'].'</div>';
        //show unlisted information
        if (!$this->listed()) {
            $out[] = "<div class='unlisted'>";
            $out[] = "This meeting is unlisted. Only people who have the link can view it.";
            if (!$this['meeting.unlisted'] && $this['meeting.closed']) {
                $out[] = "<br>It will be unlisted until ".$this->cms()->helper('strings')->date($this['meeting.start']+86400).'.';
            }
            $out[] = '</div>';
        }
        //more information link
        $out[] = '<a class="link" href="'.$this->url().'">more information</a>';
        $out[] = '</div>';
        return implode(PHP_EOL, $out);
    }

    public function parentEdgeType($child)
    {
        if ($child['dso.type'] == 'organization') {
            return 'meeting';
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

    public function dateTimeString()
    {
        $s = $this->cms()->helper('strings');
        return $s->datetime($this['meeting.start']);
    }

    public function dateString()
    {
        $s = $this->cms()->helper('strings');
        return $s->date($this['meeting.start']);
    }

    public function name($verb=null)
    {
        if ($this['digraph.name']) {
            return $this['digraph.name'];
        }
        $title = '';
        if ($this->organization()) {
            $title = ' '.$this->organization()->name();
        }
        $title .= ' Meeting';
        return $title;
    }

    public function title($verb=null)
    {
        if ($this['digraph.name']) {
            return $this['digraph.name'];
        }
        $title = $this->dateString();
        if ($this->organization()) {
            $title .= ' '.$this->organization()->name();
        }
        $title .= ' Meeting';
        return $title;
    }

    public function formMap(string $action) : array
    {
        $s = $this->cms()->helper('strings');
        $map = parent::formMap($action);
        // add tip to name indicating it's optional
        $map['digraph_name']['weight'] = 10;
        $map['digraph_name']['required'] = false;
        $map['digraph_name']['tips']['shouldbeblank'] = 'Should be left blank unless you want to override automatic naming.';
        // date
        $map['meeting_start'] = [
            'label' => 'Meeting start date/time',
            'class' => 'datetime',
            'required' => true,
            'field' => 'meeting.start',
            'weight' => 0
        ];
        // location
        $map['meeting_location'] = [
            'label' => 'Meeting location',
            'class' => 'text',
            'required' => true,
            'field' => 'meeting.location',
            'weight' => 2
        ];
        // unlisted
        $map['meeting_unlisted'] = [
            'label' => 'Unlisted',
            'class' => 'checkbox',
            'required' => false,
            'field' => 'meeting.unlisted',
            'tips' => [
                'Unlisted meetings are not shown in schedules, but are visible to anyone with the link.'
            ],
            'weight' => 11
        ];
        // closed to public
        $map['meeting_closed'] = [
            'label' => 'Closed to public',
            'class' => 'checkbox',
            'required' => false,
            'field' => 'meeting.closed',
            'tips' => [
                'Closed meetings and their attachments are hidden from the public until at least 24 hours after the meeting starts.',
                'Meeting and attachments become public once the meeting is visible.'
            ],
            'weight' => 12
        ];
        // hide title
        $map['digraph_title'] = false;
        // content label
        $map['digraph_body']['label'] = "Body content for meeting page";
        // set defaults from parent
        if ($action == 'add') {
            if (($parent = $this->cms()->package()->noun()) && $parent['meetingdefaults']) {
                foreach ($parent['meetingdefaults'] as $key => $value) {
                    if (@$map[$key]) {
                        $map[$key]['default'] = $value;
                    }
                }
            }
        }
        return $map;
    }
}
