<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'initialize.php';

if (PHP_SAPI != 'cli') redirect(\frontstage\controller::url());

\lib\database::beginTransaction();

$d_cronjob = (new \database\crontab)
    ->select([
        'expression',
        'method',
    ])
    ->where([
        ['enabled', '=', true]
    ])
    ->fetchAll();

$array_execute = [];

foreach ($d_cronjob as $object) {
    if (!(new \lib\crontab($object['expression']))->isDue()) {
        $array_execute[] = $object['method'];
    }
}

if ($array_execute) {
    $array_exclude = array_column(
        (new \database\crontablog)
            ->select([
                'method'
            ])
            ->where([
                ['state', 'in', ['pretreat', 'process']]
            ])
            ->fetchAll(),
        'method'
    );

    $array_execute = array_diff($array_execute, $array_exclude);

    $insert = [];

    foreach ($array_execute as $method) {
        $insert[] = [
            'method' => $method
        ];
    }

    (new \database\crontablog)
        ->insert($insert);
}

\lib\database::commit();
