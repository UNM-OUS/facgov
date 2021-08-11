<?php
namespace Digraph\Modules\facgov_meetings;

class RosterMember extends \Digraph\DSO\Noun
{
    protected $_organization;

    public function email()
    {
        $found = null;
        preg_replace_callback(
            '/[-0-9a-z.+_]+@[-0-9a-z.+_]+[a-z]/i',
            function ($matches) use (&$found) {
                if ($found) {
                    return;
                }
                $email = $matches[0];
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $found = $email;
                }
            },
            parent::body()
        );
        return strtolower($found);
    }

    public function sectionAnchor()
    {
        $name = 'roster-section-' . $this['member.section'];
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\-_]+/i', '_', $name);
        return $name;
    }

    public function rowAnchor()
    {
        $name = 'roster-row-' . $this['dso.id'];
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\-_]+/i', '_', $name);
        return $name;
    }

    public function hook_postEditUrl()
    {
        if ($this->organization()) {
            return $this->organization()->url('roster-membership', null, true)->string() . '#' . $this->sectionAnchor();
        }
        return $this->url('edit', null, true)->string();
    }

    public function hook_postAddUrl()
    {
        return $this->hook_postEditUrl();
    }

    public function notes()
    {
        if ($this['notes']) {
            return trim($this->cms()->helper('filters')->filterContentField(
                $this['notes'],
                $this['dso.id']
            ));
        } else {
            return '';
        }
    }

    public function body()
    {
        $body = parent::body();
        $s = $this->cms()->helper('strings');
        $body = preg_replace_callback(
            '/[-0-9a-z.+_]+@[-0-9a-z.+_]+[a-z]/i',
            function ($matches) use ($s) {
                $email = $matches[0];
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $href = $s->allHtmlEntities('mailto:' . $email);
                    $out = '<a href="' . $href . '">' . $email . '</a>';
                    return $s->obfuscate($out);
                }
                return $matches[0];
            },
            $body
        );
        return $body;
    }

    public function specialPositions($time = null)
    {
        if (!$time) {
            //return all special positions
            return $this->cms()->helper('graph')
                ->children($this['dso.id'], 'special-position');
        } else {
            //return empty if time is outside membership time
            if ($time < $this->startTime()) {
                return [];
            }
            if ($this->endTime() && $this->endTime() <= $time) {
                return [];
            }
            //filter by date
            return array_filter(
                $this->specialPositions(),
                function ($e) use ($time) {
                    if ($e->startTime() > $time) {
                        return false;
                    }
                    if ($e->endTime() && $e->endTime() <= $time) {
                        return false;
                    }
                    return true;
                }
            );
        }
    }

    public function searchIndexed()
    {
        return false;
    }

    public function name($verb = null)
    {
        $s = $this->cms()->helper('strings');
        return parent::name() .
        ($this->organization() ? ': ' . $this->organization()->name() : '') .
        ': ' . $s->date($this->startTime()) .
            ($this->endTime() ? ' - ' . $s->date($this->endTime()) : '');
    }

    public function memberName()
    {
        return $this['digraph.name'];
    }

    public function membershipTableCell()
    {
        return '<p id="' . $this->rowAnchor() . '"><strong>' . $this->memberName() . '</strong></p>' . PHP_EOL .
        $this->body();
    }

    public function startTime()
    {
        return $this['member.start'];
    }

    public function endTime()
    {
        if (!$this['member.end']) {
            return null;
        }
        return $this['member.end'];
    }

    public function parentEdgeType($child)
    {
        if ($child['dso.type'] == 'organization') {
            return 'roster-member';
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
        $map['digraph_title'] = false;
        // make name an autocomplete field when adding page
        if ($action == 'add') {
            $map['digraph_name']['class'] = RosterMemberAutocompleteField::class;
        }
        // alter body field
        $map['digraph_body']['label'] = 'Member information text';
        $map['digraph_body']['weight'] = 110;
        $map['digraph_body']['class'] = 'digraph_content_default';
        $map['digraph_body']['tips'] = [
            'Standard format is member\'s title on the first line, followed by contact information on additional lines.',
        ];
        $map['digraph_body']['call'] = [
            'filter' => ['text-safe'],
        ];
        // add notes field
        $map['notes'] = $map['digraph_body'];
        $map['notes']['label'] = 'Member notes';
        $map['notes']['field'] = 'notes';
        $map['notes']['tips'] = [
            'Notes regarding any unusual circumstances of this member\'s term. Mostly used to indicate that members resigned.',
            'If a member was appointed normally and expired normally using this field generally isn\'t necessary.',
            'These notes should be considered publicly visible.',
        ];
        $map['notes']['call'] = [
            'filter' => ['markdown-safe'],
        ];
        // member netid
        $map['netid'] = [
            'label' => 'NetID',
            'class' => 'netid',
            'field' => 'netid',
            'weight' => 105,
            'required' => false,
            'tips' => [
                'For optimal record-keeping a NetID should be entered whenever possible.',
                'The only reason this field is not required is to support some ex-officio members who don\'t have NetIDs.',
            ],
        ];
        // member section
        $map['member_section'] = [
            'label' => 'Membership table section',
            'class' => 'text',
            'field' => 'member.section',
            'weight' => 111,
            'required' => true,
            'default' => @$_REQUEST['member_section'],
            'tips' => [
                'This field controls which section of the membership list this person will appear.',
                'Generally you should not need to edit it.',
            ],
        ];
        // member type
        $map['member_type'] = [
            'label' => 'Membership table type',
            'class' => 'text',
            'field' => 'member.type',
            'weight' => 112,
            'required' => true,
            'default' => @$_REQUEST['member_type'],
            'tips' => [
                'This field controls which rows in the membership table this person will be placed in.',
                'Generally you should not need to edit it.',
            ],
        ];
        // effective date
        $map['member_start'] = [
            'label' => 'Term start date',
            'class' => 'date',
            'field' => 'member.start',
            'weight' => 101,
            'required' => true,
        ];
        // end date
        $map['member_end'] = [
            'label' => 'Term end date (optional)',
            'class' => 'date',
            'field' => 'member.end',
            'weight' => 102,
            'required' => false,
            'tips' => ['End dates are not inclusive. For example, setting an end date of August 14 would cause the member to disappear from the roster at midnight the night of August 13.'],
        ];
        return $map;
    }
}
