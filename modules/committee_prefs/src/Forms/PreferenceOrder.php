<?php
namespace Digraph\Modules\committee_prefs\Forms;

use Formward\AbstractField;
use Formward\Fields\Ordering;

class PreferenceOrder extends Ordering
{
    public function htmlValue()
    {
        $value = $this->value ?? AbstractField::submittedValue() ?? $this->default ?? null;
        return $value;
    }

    public function value($set = null)
    {
        if ($set !== null) {
            if (is_array($set)) {
                $this->setCurrentSelections($set, 'value');
            } else {
                parent::value($set);
            }
        }
        return parent::value();
    }

    function default($set = null) {
        if ($set !== null) {
            if (is_array($set)) {
                $this->setCurrentSelections($set, 'default');
            } else {
                parent::default($set);
            }
        }
        return parent::default();
    }

    public function setCurrentSelections(?array $order, string $to)
    {
        $opts = array_keys($this->opts);
        if (!$order) {
            return $opts;
        }
        $selected = array_intersect($order, $opts);
        $deleted = array_map(
            function ($e) {return "DELETE:$e";},
            array_diff($opts, $order)
        );
        if ($deleted) {
            array_push($selected, ...$deleted);
        }
        AbstractField::$to(implode(PHP_EOL, $selected));
    }
}
