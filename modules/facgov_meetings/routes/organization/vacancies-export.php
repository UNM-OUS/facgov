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
$form['recurse'] = $f->field('checkbox', 'Also recursively include all subcommittee members');
$form['submit'] = new \Formward\SystemFields\Submit('Export');

echo $form;

$download = null;
$showpreview = true;
$output = vacancies($package->noun(), $form['date']->value(), $form['recurse']->value());

// formatted for csv
$output = array_map(function ($row) {
    foreach ($row as $i => $d) {
        $d = transliterate($d);
        $d = preg_replace('/[\r\n]+/', ', ', $d);
        $d = preg_replace('/([",])/', '$1', $d);
        $row[$i] = '"' . $d . '"';
    }
    $row = implode(',', $row);
    return $row;
}, $output);

array_unshift($output, 'Committee,Section,Type');
$separator = PHP_EOL;
$download = 'csv';
$showpreview = true;
$output = implode($separator, $output);

if ($download) {
    $filename = 'vacancies_'
    . $package->noun()->name()
    . ($form['recurse']->value() ? '_ALLSUBCOMMITTEES' : '')
    . '_' . date('Ymd', $form['date']->value())
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

function vacancies($org, $time = null, $recurse = false)
{
    $vacancies = [];
    foreach ($org->roster($time) as $section => $table) {
        foreach ($table as $r) {
            if ($r['member'] === null) {
                $vacancies[] = [
                    $org->name(),
                    $section,
                    $r['type'],
                ];
            }
        }
    }
    $vacancies = array_filter($vacancies);
    //recurse and return
    if ($recurse) {
        foreach ($org->subcommittees() as $sub) {
            $vacancies = array_merge($vacancies, vacancies($sub, $time, true));
        }
    }
    return $vacancies;
}
