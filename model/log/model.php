<?php

namespace model;

class log extends \lib\model
{
    static private $id;

    private static function checkTableExist()
    {
        $tables = \database\log::getDatabase()->fetchColumn("SHOW TABLES FROM " . \database\log::$database . " LIKE " . \database\log::quote(str_replace(\database\log::$database . '.', '', \database\log::$table)));

        return $tables ? true : false;
    }

    static function getId()
    {
        if (self::$id === null && self::checkTableExist()) {
            //input
            $input = trim(file_get_contents('php://input'));

            //server
            $server = [];

            if (isset($_SERVER['HTTP_REFERER'])) $server['HTTP_REFERER'] = $_SERVER['HTTP_REFERER'];
            if (isset($_SERVER['REQUEST_URI'])) $server['REQUEST_URI'] = $_SERVER['REQUEST_URI'];

            //session
            $session = \lib\session::get();

            //user_id
            $user_id = \model\user::getSession()['user_id'];

            self::$id = (new \database\log)
                ->insert([
                    'cookie' => ($_COOKIE) ? json_encode($_COOKIE) : null,
                    '`get`' => ($_GET) ? json_encode($_GET) : null,
                    'headers' => json_encode(getallheaders()),
                    'input' => ($input === '') ? null : $input,
                    'ip' => remote_ip(),
                    'post' => ($_POST) ? json_encode($_POST) : null,
                    'runtime' => runtime(),
                    '`server`' => ($server) ? json_encode($server) : null,
                    '`session`' => ($session) ? json_encode($session) : null,
                    'session_id' => \lib\session::getID(),
                    'user_id' => ($user_id) ? $user_id : null,
                ]);
        }

        return self::$id;
    }

    static function setLog(array $param = null)
    {
        if (self::checkTableExist()) {
            $DatabaseLog = (new \database\log)
                ->select([
                    'error',
                    'exception',
                    '`return`',
                ])
                ->where([['log_id', '=', self::getId()]])
                ->fetch();

            $update = [];

            if (isset($param['error'])) {
                $update['error'] = (replaceSpace($DatabaseLog['error']) === '') ? $param['error'] : $DatabaseLog['error'] . "\r\n" . $param['error'];
            }

            if (isset($param['exception'])) {
                $update['exception'] = (replaceSpace($DatabaseLog['exception']) === '') ? $param['exception'] : $DatabaseLog['exception'] . "\r\n" . $param['exception'];
            }

            if (isset($param['return'])) {
                $update['`return`'] = $param['return'];
            }

            (new \database\log)
                ->where([['log_id', '=', self::getId()]])
                ->update(array_merge(
                        [
                            'runtime' => runtime()
                        ],
                        $update
                    )
                );
        }
    }

    static function setError()
    {
        $errorArray = error_get_last();

        if ($errorArray) {
            self::setLog([
                'error' => $errorArray['message'] . ' in ' . $errorArray['file'] . ' on line ' . $errorArray['line'],
            ]);
        }
    }

    static function setException($level, $message)
    {
        $Exception = (new \lib\exception)
            ->setLevel($level)
            ->setMessage($message);

        self::setLog([
            'exception' => $Exception->getTraceString(),
        ]);

        $Exception->output();
    }

    static function setReturn($r)
    {
        if ($r) {
            self::setLog([
                'return' => json_encode($r),
            ]);
        }
    }
}
