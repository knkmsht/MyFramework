<?php

/**
 * DataBase
 * 註一: 參考 http://stackoverflow.com/questions/20079320/php-pdo-mysql-how-do-i-return-integer-and-numeric-columns-from-mysql-as-int
 */

namespace lib;

class databaseabstract extends \PDO
{
    function __construct($obj)
    {
        try {
            parent::__construct(
                $obj['DSN'],
                $obj['USER'],
                $obj['PASSWORD'],
                [
                    \PDO::ATTR_EMULATE_PREPARES => false,//2015-11-04 Lion: 依資料型態回傳(註一)
                    \PDO::ATTR_PERSISTENT => false,//2018-01-19 Lion: 持久連接無法有效地建立事務處理, 參考 https://stackoverflow.com/questions/3765925/persistent-vs-non-persistent-which-should-i-use
                    \PDO::ATTR_STRINGIFY_FETCHES => false,//2015-11-04 Lion: 依資料型態回傳(註一)
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                ]
            );
        } catch (\PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
        }
    }

    function beginTransaction()
    {
        parent::beginTransaction();
    }

    function commit()
    {
        parent::commit();
    }

    function errorCode()
    {
        return parent::errorCode();
    }

    function errorInfo()
    {
        return parent::errorInfo();
    }

    function exec($sql)
    {
        $return = parent::exec($sql);

        if ($this->errorCode() !== '00000') {
            \model\log::setException(\lib\exception::LEVEL_ERROR, $this->errorInfo()[2] . ' in "' . $sql . '".');
        }

        return $return;
    }

    function fetchColumn($sql)
    {
        $return = false;//2017-10-20 Lion: fetchColumn 在沒找到東西時是回傳 boolean false, 故預設 return boolean false

        $query = $this->query($sql);

        if (is_object($query)) {
            $return = $query->fetchColumn();
        }

        return $return;
    }

    function fetch($sql, $fetchType = \PDO::FETCH_ASSOC)
    {
        $return = false;//2017-10-20 Lion: fetch 在沒找到東西時是回傳 boolean false, 故預設 return boolean false

        $query = $this->query($sql);

        if (is_object($query)) {
            $return = $query->fetch($fetchType);
        }

        return $return;
    }

    function fetchAll($sql, $fetchType = \PDO::FETCH_ASSOC)
    {
        $return = [];//2017-10-19 Lion: fetchAll 在沒找到東西時是回傳空陣列, 故預設 return  []

        $query = $this->query($sql);

        if (is_object($query)) {
            $return = $query->fetchAll($fetchType);
        }

        return $return;
    }

    function query($sql)
    {
        $result = parent::query($sql);

        if ($this->errorCode() !== '00000') {
            \model\log::setException(\lib\exception::LEVEL_ERROR, $this->errorInfo()[2] . ' in "' . $sql . '".');
        }

        return $result;
    }

    function quote($var, $quote = true)
    {
        if ($var === null) {
            $return = 'NULL';
        } elseif ($var === true) {
            $return = 'TRUE';
        } elseif ($var === false) {
            $return = 'FALSE';
        } else {
            $return = (is_string($var) && $quote) ? parent::quote($var) : $var;
        }

        return $return;
    }

    function rollBack()
    {
        try {
            return parent::rollBack();
        } catch (\PDOException $e) {
            \model\log::setException(\lib\exception::LEVEL_NOTICE, $e->getMessage() . '.');
        }
    }
}
