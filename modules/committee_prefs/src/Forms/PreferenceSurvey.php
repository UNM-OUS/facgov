<?php
namespace Digraph\Modules\committee_prefs\Forms;

use Digraph\Modules\facgov_meetings\Organization;
use Digraph\Modules\ous_event_management\EventGroup;
use Digraph\Modules\ous_event_management\SignupWindow;

/**
 * The preference surveys are based on the event system's signup windows,
 * but do not require an event group. Instead, they just always return a
 * dummy event group that doesn't really exist.
 *
 * Since they don't need to be configurable in anywhere near the same way,
 * they also have their chunks hard-coded.
 */
class PreferenceSurvey extends SignupWindow
{
    const ROUTING_NOUNS = ['event-signupwindow', 'preference-survey'];

    /**
     * Shouldn't have a parent for the way this function is used
     *
     * @return void
     */
    public function parent()
    {
        return null;
    }

    public function actions($links)
    {
        parent::actions($links);
        $links['invited-emails'] = '!id/invited-emails';
        return $links;
    }

    public function vacancyCount(Organization $org): int
    {
        return count($this->facultyVacancies($org));
    }

    public function facultyVacancies(Organization $org): array
    {
        $name = md5(serialize(['vacancyCount', $this['dso.id'], $org['dso.id']]));
        $cache = $this->cms()->cache();
        if ($cache->hasItem($name)) {
            return $cache->getItem($name)->get();
        }
        $roster = $org->roster($this['appointmentstart']);
        $vacancies = [];
        foreach ($roster as $category => $members) {
            foreach ($members as $member) {
                if (!$member['member']) {
                    $vacancies[] = [
                        'category' => $category,
                        'type' => $member['type'],
                    ];
                }
            }
        }
        $vacancies = array_filter(
            $vacancies,
            [$this, 'vacancyFilter']
        );
        $citem = $cache->getItem($name);
        $citem->set($vacancies);
        $cache->save($citem);
        return $vacancies;
    }

    public function vacancyFilter($v): bool
    {
        // entirely omitted categories
        if (in_array($v['category'], ['student', 'ex-officio', 'alumni', 'administrative', 'administrative non-voting', 'regent', 'president of the university'])) {
            return false;
        }
        // entirely omitted types
        if (in_array($v['type'], ['faculty senate president', 'faculty senate president-elect', 'past faculty senate president'])) {
            return false;
        }
        // omit types that look like a chair
        if (preg_match('/ (co-)?chair$/', $v['type'])) {
            return false;
        }
        // return true by default
        return true;
    }

    /**
     * Chunks are hard-coded and can't be configured on the user side
     *
     * @return array
     */
    public function chunks(): array
    {
        return [
            'contact' => ContactChunk::class,
            'sabbatical' => SabbaticalChunk::class,
            'preferences' => PreferenceChunk::class,
        ];
    }

    /**
     * On creation this class will find the most recently-created other
     * survey, and copy its list of options as a starting point.
     *
     * @return void
     */
    public function hook_create()
    {
        parent::hook_create();
        // try to find a recent preference survey, and copy its options
        $search = $this->cms()->factory()->search();
        $search->where('${dso.type} = "preference-survey" AND ${dso.id} <> :id');
        $search->order('${dso.created.date} desc');
        $search->limit(1);
        if ($result = $search->execute(['id' => $this['dso.id']])) {
            $result = reset($result);
            $this->options($result->options());
        }
    }

    /**
     * The form is almost the same as a default signup window, mostly
     * things are removed for not being relevant.
     *
     * @param string $action
     * @return array
     */
    public function formMap(string $action): array
    {
        $map = parent::formMap($action);
        // add field for specifying start date of appointments
        $map['appointmentstart'] = [
            'label' => 'Appointment start date',
            'class' => 'date',
            'field' => 'appointmentstart',
            'weight' => 300,
            'required' => 'true',
            'default' => strtotime('July 1'),
        ];
        // a lot of the default configuration options don't really make sense here
        $map['signupwindow_form_preset'] = false;
        $map['signupwindow_unlisted'] = false;
        $map['signup_windowtype'] = false;
        $map['signup_grouping'] = false;
        // limiting defaults is true and hidden
        $map['signupwindow_limit_signups']['default'] = true;
        $map['signupwindow_limit_signups']['call']['addClass'] = ['hidden'];
        return $map;
    }

    /**
     * Return a list of the organizations that are options on this form. Options
     * are stored as edges from the organization to this survey, of the type
     * 'preference-survey'
     *
     * Can also be given an array of Organizations and/or IDs/slugs to set the
     * list of options. Note that setting options will clear any existing options,
     * so to append to options you need to get them, append to them, and then
     * re-set the entire list of options.
     *
     * @param array $set
     * @return array
     */
    public function options(array $set = null): array
    {
        if ($set !== null) {
            // filter and clean up edges list, so it's only the IDs of organizations
            $set = array_filter(array_map(
                function ($e) {
                    if (is_string($e)) {
                        $e = $this->cms()->read($e);
                    }
                    if ($e instanceof Organization) {
                        return $e['dso.id'];
                    }
                    return false;
                },
                $set
            ));
            // get edge helper
            $edges = $this->cms()->helper('edges');
            // delete existing preference-survey edges
            $edges->deleteParents($this['dso.id'], 'preference-survey');
            // add new preference-survey edges
            foreach ($set as $org) {
                $edges->create($org, $this['dso.id'], 'preference-survey');
            }
        }
        $out = [];
        foreach ($this->cms()->helper('graph')->parents($this['dso.id'], 'preference-survey') as $opt) {
            $out[$opt['dso.id']] = $opt;
        }
        return $out;
    }

    /**
     * Creates a dummy EventGroup that doesn't actually exist in the database
     *
     * @return EventGroup
     */
    public function eventGroup(): EventGroup
    {
        return new EventGroup(
            ['digraph.name' => 'Dummy event group'],
            $this->factory()
        );
    }
}
