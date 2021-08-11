<?php
namespace Digraph\Modules\committee_prefs\Forms;

use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;
use Formward\Fields\DateRange;

class SabbaticalChunk extends AbstractChunk
{
    protected $label = 'Sabbatical or major leave';

    public function body_complete()
    {
        if ($this->signup[$this->name.'.start']) {
            echo "Sabbatical or leave from ".$this->signup[$this->name.'.start']." to ".$this->signup[$this->name.'.end'];
        }else {
            echo "No sabbatical or significant leave planned";
        }
    }

    protected function form_map(): array
    {
        return [
            'range' => [
                'label' => 'Expected date range',
                'class' => DateRange::class,
                'field' => $this->name,
                'required' => false,
                'tips' => [
                    'Please enter the start and end dates if you expect to be on sabbatical or significant leave.',
                    'Please note that if you will be on leave during either or both semesters you will not be appointed to any committee. But, should a vacancy arise in a committee you are interested in and during a semester you are on campus, we may contact you.'
                ]
            ]
        ];
    }
}
