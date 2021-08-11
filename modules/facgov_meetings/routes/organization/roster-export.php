<?php
$package->cache_noCache();
$f = $cms->helper('forms');
$s = $cms->helper('strings');
setlocale(LC_ALL, 'en_US');

$form = new \Formward\Fields\Container('', 'date');
$form->method('get');
$form->tag = 'form';
$form->addClass('Form');
$form->addClass('compact-form');
$form['date'] = $cms->helper('forms')->field('date', '');
$form['date']->default(time());
$form['format'] = $f->field('select', 'Format');
$form['format']->required('true');
$form['format']->options([
    'listserv' => 'Adding to listserv with bulk operations',
    'email' => 'Email to/cc/bcc field',
    'opinio' => 'CSV for Opinio invites',
    'csv' => 'Complete CSV file',
    'memlist' => 'Membership list',
]);
$form['format']->default('listserv');
$form['recurse'] = $f->field('checkbox', 'Also recursively include all subcommittee members');
$form['exclude-nonvoting'] = $f->field('checkbox', 'Exclude non-voting members');
$form['exclude-exoff'] = $f->field('checkbox', 'Exclude ex-officio members');
$form['submit'] = new \Formward\SystemFields\Submit('Export');

echo $form;
echo "<p><a href='" . $package->noun()->url('roster-export-all') . "'>View/print this roster and all subcommittees in human-readable form</a></p>";

$download = null;
$showpreview = true;
// get full membership list
$output = $package->noun()->members(
    $form['date']->value(),
    $form['recurse']->value()
);

// method for output filtering
function output_filter($arr, $str)
{
    return array_filter(
        $arr,
        function ($e) use ($str) {
            return (stripos($e['member.section'], $str) === false)
                && (stripos($e['member.type'], $str) === false);
        }
    );
}

// filter out non-voting
if ($form['exclude-nonvoting']->value()) {
    $output = output_filter($output, 'non-voting');
}

// filter out ex-officio
if ($form['exclude-exoff']->value()) {
    $output = output_filter($output, 'ex-officio');
}

// remove duplicates by email address for certain formats
if (!in_array($form['format']->value(), ['memlist'])) {
    $emails = [];
    foreach ($output as $member) {
        if ($email = $member->email()) {
            if (!isset($emails[$email])) {
                $emails[$email] = $member;
            }
        }
    }
    $output = $emails;
}

if ($form['format']->value() == 'memlist') {
    // formatted as a table that isn't deduplicated by email
    // formatted for csv
    array_walk(
        $output,
        function (&$member, $email) use ($s) {
            $info = preg_split('/[\r\n]+/', $member['digraph.body.text']);
            $member = [
                $member->organization()->name(),
                $member['digraph.name'],
                $info[0],
                $info[1],
                $s->date($member->startTime()),
                $s->date($member->endTime() - 86400 + 60),
                $member->email(),
                $member['member.section'],
                $member['member.type'],
            ];
            foreach ($member as $i => $d) {
                $d = transliterate($d);
                $d = preg_replace('/[\r\n]+/', ', ', $d);
                $d = preg_replace('/([",])/', '$1', $d);
                $member[$i] = '"' . $d . '"';
            }
            $member = implode(',', $member);
        }
    );
    array_unshift($output, 'Committee,Name,Rank,Department,Start,End,Email,Section,Type');
    $separator = PHP_EOL;
    $download = 'csv';
    $showpreview = true;
} elseif ($form['format']->value() == 'listserv') {
    // formatted for listserv bulk actions
    array_walk(
        $output,
        function (&$member, $email) {
            $member = $email . ' ' . $member['digraph.name'];
        }
    );
    $download = 'txt';
    $separator = PHP_EOL;
} elseif ($form['format']->value() == 'opinio') {
    //formatted for csv
    array_walk(
        $output,
        function (&$member, $email) use ($s) {
            $member = [
                $member['digraph.name'],
                $email,
                $member['netid'],
            ];
            foreach ($member as $i => $d) {
                $d = transliterate($d);
                $d = preg_replace('/[\r\n]+/', ', ', $d);
                $d = preg_replace('/[",]/', '', $d);
                $member[$i] = $d;
            }
            $member = implode(',', $member);
        }
    );
    array_unshift($output, 'Name,Email,NetID');
    $separator = PHP_EOL;
    $download = 'csv';
    $showpreview = false;
} elseif ($form['format']->value() == 'csv') {
    //formatted for csv
    $s = $cms->helper('strings');
    array_walk(
        $output,
        function (&$member, $email) use ($s) {
            $member = [
                $member['digraph.name'],
                $email,
                $member['netid'],
                $s->date($member->startTime()),
                $s->date($member->endTime() - 86400 + 60),
            ];
            foreach ($member as $i => $d) {
                $d = transliterate($d);
                $d = str_replace('"', '""', $d);
                $member[$i] = '"' . $d . '"';
            }
            $member = implode(',', $member);
        }
    );
    array_unshift($output, 'Name,Email,NetID,Start,End');
    $separator = PHP_EOL;
    $download = 'csv';
    $showpreview = false;
} else {
    //formatted for email
    array_walk(
        $output,
        function (&$member, $email) {
            $member = '"' . str_replace('"', '\"', $member['digraph.name']) . '" &lt;' . $email . '&gt;';
        }
    );
    $download = 'txt';
    $separator = '; ' . PHP_EOL;
}

$output = implode($separator, $output);

if ($download) {
    $filename = $package->noun()->name()
    . ' ' . $form['format']->value()
    . ($form['recurse']->value() ? ' SUBCOM' : '')
    . ($form['exclude-nonvoting']->value() ? ' VOTING' : '')
    . ($form['exclude-exoff']->value() ? ' NOEXOFF' : '')
    . ' ' . date('Ymd', $form['date']->value())
        . '.' . $download;
    //download link
    $url = $package->url();
    $url['args.z_dl'] = md5($filename);
    echo "<p><strong>Download:</strong><br><a href='$url'>$filename</a></p>";
    //prepare download file
    if ($package['url.args.z_dl'] == md5($filename)) {
        $package->makeMediaFile(
            $filename
        );
        $package->binaryContent($output);
        return;
    }
}

if ($showpreview) {
    echo "<p><strong>Preview:</strong></p>";
    echo "<pre>$output</pre>";
}

function transliterate($string)
{
    $string = strtr(
        utf8_decode($string),
        utf8_decode(
            'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'
        ),
        'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'
    );
    return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
}
