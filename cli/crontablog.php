<?php
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'initialize.php';

if (PHP_SAPI != 'cli') redirect(\frontstage\controller::url());

\lib\database::beginTransaction();

$d_crontablog = (new \database\crontablog)
    ->select([
        'crontablog_id',
        'method',
        'param',
    ])
    ->where([
        ['state', '=', 'pretreat']
    ])
    ->order([
        'inserttime' => 'asc'
    ])
    ->limit('0,1')
    ->lockForUpdate()
    ->fetch();

if ($d_crontablog) {
    try {
        $return = call_user_func($d_crontablog['method'], json_decode($d_crontablog['param'], true));

        list ($result, $message) = return_decode($return);

        $state = ($result == \lib\result::SYSTEM_OK) ? 'success' : 'fail';
    } catch (\Throwable $throwable) {
        $state = 'fail';

        $exception = (new \lib\exception)
            ->setLevel(\lib\exception::LEVEL_NOTICE)
            ->setMessage($throwable->getMessage());
    }

    (new \database\crontablog)
        ->where([
            ['crontablog_id', '=', $d_crontablog['crontablog_id']]
        ])
        ->update([
            'exception' => isset($exception) ? $exception->getTraceString() : null,
            'mysql_connection_id' => \database\crontablog::getConnectionId(),
            '`return`' => isset($return) ? json_encode($return) : null,
            'runtime' => runtime(),
            'state' => $state,
        ]);

    (new \database\crontab)
        ->where([
            ['method', '=', $d_crontablog['method']]
        ])
        ->update([
            'lastexecutiontime' => \lib\datetime::gettime()
        ]);
}

\lib\database::commit();
