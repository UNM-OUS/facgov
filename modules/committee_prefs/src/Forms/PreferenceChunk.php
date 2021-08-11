<?php
namespace Digraph\Modules\committee_prefs\Forms;

use Digraph\Modules\facgov_meetings\Organization;
use Digraph\Modules\ous_event_management\Chunks\AbstractChunk;
use Formward\Fields\Number;

class PreferenceChunk extends AbstractChunk
{
    protected $label = 'Committee preferences';

    public function body_complete()
    {
        $data = $this->signup[$this->name];
        echo "<dl>";
        if ($data['max']) {
            echo "<dt>Max committees preferred</dt><dd>" . $data['max'] . "</dd>";
        }
        echo "<dt>Ad-hoc committees</dt><dd>" . ($data['adhoc'] ? 'Willing' : 'Unwilling') . "</dd>";
        if ($data['order']) {
            echo "<dt>Preferred committees</dt>";
            echo "<dd><ol>";
            foreach ($data['order'] as $n) {
                $n = $this->signup->cms()->read($n);
                echo "<li>";
                echo "<a href='" . $n->url() . "' target='_blank'>" . $n->name() . "</a>";
                echo "</li>";
            }
            echo '</ol></dd>';
        }
        echo "</dl>";
    }

    protected function form_map(): array
    {
        $map = [];
        $map['max'] = [
            'label' => 'Maximum number of committees I\'m willing to serve on',
            'field' => $this->name . '.max',
            'class' => Number::class,
            'weight' => 500,
            'required' => true,
            'tips' => ['This will help balance the positions that need to be filled with the workload each interested faculty member is willing to take on.'],
        ];
        $map['adhoc'] = [
            'label' => 'I am willing to serve on search and ad hoc committees',
            'field' => $this->name . '.adhoc',
            'class' => 'checkbox',
            'weight' => 550,
        ];
        $map['order'] = [
            'label' => 'Order of preference for committee assignments',
            'field' => $this->name . '.order',
            'class' => PreferenceOrder::class,
            'weight' => 900,
            'required' => true,
            'tips' => [
                'Drag and drop to place the committees in the order of your preference for serving, with your preferred placements at the top.',
                'You can also click the trash icon to indicate that you do not wish to be considered at all for serving on a committee.',
            ],
            'call' => [
                'allowDeletion' => [true],
                'opts' => [$this->fieldOpts()],
            ],
        ];
        return $map;
    }

    protected function fieldOpts(): array
    {
        $opts = $this->signup->signupWindow()->options();
        uasort(
            $opts,
            function (Organization $a, Organization $b) {
                $a = $this->signup->signupWindow()->vacancyCount($a);
                $b = $this->signup->signupWindow()->vacancyCount($b);
                return $b - $a;
            }
        );
        $opts = array_map(
            function (Organization $org) {
                $out = '<strong>' . $org->name() . '</strong><br>';
                $vacancies = $this->signup->signupWindow()->vacancyCount($org);
                $out .= "approximately $vacancies faculty vacanc" . ($vacancies == 1 ? 'y' : 'ies') . " expected";
                return $out;
            },
            $opts
        );
        return $opts;
    }
}
