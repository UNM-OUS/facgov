<?php
namespace Digraph\Modules\committee_prefs\Forms;

use Digraph\Modules\ous_event_management\Chunks\Contact\FacultyContactInformation;

class ContactChunk extends FacultyContactInformation
{
    public function instructions(): ?string
    {
        return '';
    }

    public function body_complete()
    {
        echo "<dl>";
        if ($this->name()) {
            echo "<dt>Name</dt><dd>" . $this->name() . "</dd>";
        }
        if ($this->email()) {
            echo "<dt>Email</dt><dd>" . $this->email() . "</dd>";
        }
        if ($this->phone()) {
            echo "<dt>Phone</dt><dd>" . $this->phone() . "</dd>";
        }
        if ($this->signup[$this->name.'.college']) {
            echo "<dt>School/college/campus</dt><dd>" . $this->signup[$this->name.'.college'] . "</dd>";
        }
        if ($this->signup[$this->name.'.department']) {
            echo "<dt>Department</dt><dd>" . $this->signup[$this->name.'.department'] . "</dd>";
        }
        if ($this->signup[$this->name.'.title']) {
            echo "<dt>Rank/title</dt><dd>" . $this->signup[$this->name.'.title'] . "</dd>";
        }
        if ($this->signup[$this->name.'.tenure']) {
            echo "<dt>Tenure</dt><dd>Tenured</dd>";
        }else {
            echo "<dt>Tenure</dt><dd>Not tenured</dd>";
        }
        echo "</dl>";
    }

    public function hook_update()
    {
        if (!$this->signup[$this->name]) {
            // try to find previous signups by this user
            $search = $this->signup->cms()->factory()->search();
            $search->where('${dso.type} = :type AND ${signup.for} = :for');
            $search->order('${dso.created.date} desc');
            $search->limit(1);
            if ($result = $search->execute(['type' => $this->signup['dso.type'],'for'=>$this->signup['signup.for']])) {
                $result = array_pop($result);
                if ($result[$this->name]) {
                    $this->signup[$this->name] = $result[$this->name];
                    return;
                }
            }
            // try to find user in user lists
            if ($user = $this->userListUser()) {
                $this->signup[$this->name] = [
                    'firstname' => $user['first name'],
                    'lastname' => $user['last name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'tenure' => $user['tenure desc'] == 'Tenured',
                    'college' => $user['org level 3 desc'],
                    'department' => $user['org desc'],
                    'title' => $user['pcls desc'],
                ];
            }
        }
    }

    protected function form_map(): array
    {
        $map = parent::form_map();
        $map['college'] = [
            'label' => "School/College/Campus",
            'field' => $this->name . '.college',
            'class' => 'text',
            'weight' => 300,
            'required' => true,
        ];
        $map['Department'] = [
            'label' => "Department",
            'field' => $this->name . '.department',
            'class' => 'text',
            'weight' => 300,
            'required' => true,
        ];
        $map['title'] = [
            'label' => "Rank/Title",
            'field' => $this->name . '.title',
            'class' => 'text',
            'weight' => 300,
            'required' => true,
        ];
        $map['tenure'] = [
            'label' => "I am tenured",
            'field' => $this->name . '.tenure',
            'class' => 'checkbox',
            'weight' => 500,
        ];
        unset($map['phone']['tips']);
        return $map;
    }
}
