<?php

/**
 * 2017-01-26 Lion:
 *     各 Model 自訂的函式類型, 並依各 table 資料類型有所近似的處理
 *     table 類型:
 *         1. Object table (例如 user)
 *             a. deleteXXX 用 delete, 嚴格檢查參數
 *             b. insertXXX 用 insert, 嚴格檢查參數
 *             c. updateXXX 用 update, 嚴格檢查參數
 *
 *
 *         2. Relational table (例如 user2facebook)
 *             a. deleteXXX 用 delete
 *             b. insertXXX 用 replace
 *             c. 沒有 updateXXX
 *             d. 沒有 ableToDelete
 *             e. ableToInsert 嚴格檢查參數
 *             f. 沒有 ableToUpdate
 *
 *     函示類型:
 *         1. ableToXXX: 檢查參數, 僅有這類函式才回傳 array_encode_return
 * @return array_encode_return()
 *
 *         2. getXXX: 回傳 object; 一般第一層 key 為 table name, 第二層為 array 或 object
 * @return object
 *
 *         3. insertXXX: insert 處理
 *
 *         4. isXXX: 檢查邏輯 / 參數是否存在 (不檢查資料是否存在)
 * @return boolean
 *
 *         5. updateXXX: update 處理
 *
 *         6. usable: 檢查資料是否存在
 * @return array_encode_return()
 */

namespace lib;

class model
{
    public static $switch_memcache = false;
    private static $switch_debug = false;
    private static $switch_transaction = false;
    private static $separate1 = '<#>';
    protected static $database_instance = [];
    protected static $memcache_instance = [];

    protected $column = [];
    protected $join = [];
    protected $where = [];
    protected $group = [];
    protected $having = [];
    protected $order = [];
    protected $limit;
    protected $lock;

    protected $sql;

    function __construct()
    {
    }

    function __construct_child()
    {
        self::setDatabase(static::$database);

        if (self::$switch_memcache && !isset(self::$memcache_instance[static::$memcache])) self::$memcache_instance[static::$memcache] = new \lib\Memcache(core::$_config['MC'][static::$memcache]);
    }

    function __destruct()
    {
    }

    function beginTransaction()
    {
        if (self::$switch_transaction === false) {
            $array0 = [];

            foreach (\Core::$_config['DataBase'] as $database => $v_0) {
                if (!in_array($v_0['DSN'], $array0)) {
                    $array0[] = $v_0['DSN'];

                    self::getDatabase($database)->beginTransaction();
                }
            }

            self::$switch_transaction = true;
        }

        return true;
    }

    function cachekey_encode($sql, $expire, $debug)
    {
        return implode(self::$separate1, [$sql, $expire, $debug]);
    }

    function select(array $param = null)
    {
        if ($param) {
            $param = array_filter($param, function ($v0) {
                return $v0 !== null;
            });
            $this->column = array_merge($this->column, $param);
        }

        return $this;
    }

    function commit()
    {
        if (self::$switch_transaction === true) {
            $array0 = [];

            foreach (self::$database_instance as $k_0 => $v_0) {
                if (!in_array(\Core::$_config['DataBase'][$k_0]['DSN'], $array0)) {
                    $array0[] = \Core::$_config['DataBase'][$k_0]['DSN'];

                    $v_0->commit();
                }
            }

            self::$switch_transaction = false;
        }

        return true;
    }

    function connection_id()
    {
        return self::$database_instance[static::$database]->fetchColumn('SELECT CONNECTION_ID()');
    }

    function delete()
    {
        $sql = 'Delete from ' . \config\database::$Prefix . static::$database . '.' . static::$table;

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
        }

        self::$database_instance[static::$database]->exec($sql);

        if (self::$switch_debug) {
            echo '#[sql]<br>' . $sql . ';<br>';
            self::$switch_debug = false;
        }//debug

        return true;
    }

    function fetch($expire = null)
    {
        return $this->fetch_logic(__FUNCTION__, $expire);
    }

    function fetchAll($expire = null)
    {
        return $this->fetch_logic(__FUNCTION__, $expire);
    }

    function fetchColumn($expire = null)
    {
        return $this->fetch_logic(__FUNCTION__, $expire);
    }

    function fetchEnum($field)
    {
        preg_match("/^enum\(\'(.*)\'\)$/", self::$database_instance[static::$database]->fetch('SHOW COLUMNS FROM ' . \config\database::$Prefix . static::$database . '.' . static::$table . ' WHERE Field = ' . self::$database_instance[static::$database]->quote($field))['Type'], $matches);

        return $matches ? explode("','", $matches[1]) : null;
    }

    function fetch_logic($fetch_case, $expire)
    {
        $sql = 'SELECT';
        $sql .= ($this->column) ? ' ' . implode(',', array_map('trim', $this->column)) : ' ' . static::$table . '.*';
        $sql .= ' FROM ' . \config\database::$Prefix . static::$database . '.' . static::$table;
        if (!empty($this->join)) $sql .= ' ' . implode(' ', $this->join);

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
        }

        if (!empty($this->group)) $sql .= ' GROUP BY ' . implode(',', array_map('trim', $this->group));
        if (!empty($this->order)) $sql .= ' ORDER BY ' . implode(',', $this->order);
        if (!empty($this->limit)) $sql .= ' LIMIT ' . $this->limit;
        if (!empty($this->lock)) $sql .= ' ' . $this->lock;

        if (self::$switch_memcache && $expire === null) $expire = self::$memcache_instance[static::$memcache]->expire;

        $a_debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[0];
        $debug = $a_debug['file'] . ' on line ' . $a_debug['line'];
        $cachekey = $this->cachekey_encode($sql, $expire, $debug);

        if (self::$switch_debug) {
            echo '#[sql]<br>' . $sql . ';<br>';
            self::$switch_debug = false;
        }//debug

        $data = null;

        if (self::$switch_memcache && self::$memcache_instance[static::$memcache]->exists($cachekey) && $this->lock === null) {
            $data = self::$memcache_instance[static::$memcache]->get($cachekey);
        } else {
            $data = self::$database_instance[static::$database]->$fetch_case($sql);
            if (self::$switch_memcache) self::$memcache_instance[static::$memcache]->set($cachekey, $data, $expire);
        }

        return $data;
    }

    static function getDatabase($database)
    {
        self::setDatabase($database);

        return self::$database_instance[$database];
    }

    function group(array $group = null)
    {
        if ($group) {
            $group = array_filter($group, function ($v0) {
                return $v0 !== null;
            });
            $this->group = array_merge($this->group, $group);
        }

        return $this;
    }

    function having()
    {

    }

    function insert(array $param)
    {
        if ($param) {
            self::$database_instance[static::$database]->exec($this->insertLogic($param));

            return (int)self::$database_instance[static::$database]->lastInsertId();//沒有 AUTO_INCREMENT 或是寫入失敗會得到 0
        }
    }

    function insertLogic(array $param, $replace = false)
    {
        $a_column = [];
        $s_value = null;

        switch (array_depth($param)) {
            case 1:
                $a_column = array_keys($param);
                $s_value = '(' . implode(',', array_map([self::$database_instance[static::$database], 'quote'], $param)) . ')';
                break;

            case 2:
                $a_column = array_keys(reset($param));
                $a_value = [];

                foreach ($param as $v0) {
                    $a_value[] = '(' . implode(',', array_map([self::$database_instance[static::$database], 'quote'], $v0)) . ')';
                }

                $s_value = implode(',', $a_value);
                break;

            default:
                throw new \Exception('Unknown case');
                break;
        }

        $sql = 'INSERT INTO ' . \config\database::$Prefix . static::$database . '.' . static::$table . ' (' . implode(',', $a_column) . ') VALUES ' . $s_value;

        if ($replace) {
            $tmp0 = [];

            foreach ($a_column as $v0) {
                $tmp0[] = $v0 . '=VALUES(' . $v0 . ')';
            }

            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(',', $tmp0);
        }

        if (self::$switch_debug) {
            echo '#[sql]<br>' . $sql . ';<br>';
            self::$switch_debug = false;
        }//debug

        return $sql;
    }

    function join(array $join = null)
    {
        if ($join) {
            foreach ($join as $v1) {
                list ($join_case, $join_table, $join_condition) = $v1;

                $string_0 = '\Model\\' . $join_table;

                $join_model = new $string_0;

                $this->join[] = strtolower(trim($join_case)) . ' ' . \config\database::$Prefix . $join_model::$database . '.' . trim($join_table) . ' ' . trim($join_condition);
            }
        }

        return $this;
    }

    function kill($connection_id)
    {
        self::$database_instance[static::$database]->exec('KILL ' . $connection_id);
    }

    function limit($limit = null)
    {
        if ($limit) $this->limit = str_replace(' ', '', $limit);

        return $this;
    }

    function debug()
    {
        self::$switch_debug = true;

        return $this;
    }

    function lock($lock)
    {
        $this->lock = strtolower(trim($lock));

        return $this;
    }

    function order(array $order = null)
    {
        if ($order) {
            foreach ($order as $k1 => $v1) {
                $this->order[] = trim($k1) . ' ' . strtolower(trim($v1));
            }
        }

        return $this;
    }

    function sql($sql)
    {
        $this->sql = $sql;

        return $this;
    }

    function quote($value, $quote = true)
    {
        return self::$database_instance[static::$database]->quote($value, $quote);
    }

    function replace(array $param)
    {
        if ($param) self::$database_instance[static::$database]->exec($this->insertLogic($param, true));

        return true;
    }

    function rollBack()
    {
        if (self::$switch_transaction === true) {
            $tmp0 = [];

            foreach (self::$database_instance as $k_0 => $v_0) {
                if (!in_array(core::$_config['DataBase'][$k_0]['DSN'], $tmp0)) {
                    $tmp0[] = core::$_config['DataBase'][$k_0]['DSN'];

                    $v_0->rollBack();
                }
            }

            self::$switch_transaction = false;
        }

        return true;
    }

    static private function setDatabase($database)
    {
        if (!isset(self::$database_instance[$database])) {
            self::$database_instance[$database] = new \lib\databaseabstract(\config\database::settings()[$database]);
        }
    }

    function truncate()
    {
        self::$database_instance[static::$database]->exec('TRUNCATE TABLE ' . \config\database::$Prefix . static::$database . '.' . static::$table);
    }

    function update(array $param)
    {
        if ($param) {
            $tmp0 = [];

            foreach ($param as $k0 => $v0) {
                $quote = true;

                if (is_array($v0)) list ($v0, $quote) = $v0;

                $tmp0[] = $k0 . '=' . $this->quote($v0, $quote);
            }

            $sql = 'UPDATE ' . \config\database::$Prefix . static::$database . '.' . static::$table . ' SET ' . implode(',', $tmp0);

            if (!empty($this->where)) {
                $sql .= ' WHERE ' . implode(' ', $this->where);
            }

            self::$database_instance[static::$database]->exec($sql);
        }

        if (self::$switch_debug) {
            echo '#[sql]<br>' . $sql . ';<br>';
            self::$switch_debug = false;
        }//debug

        return true;
    }

    /**
     * when case 格式範例
     *     key=>array(
     *         'when'=>array(
     *             array(FIELD, OPERATOR, VALUE, THEN)
     *             [, array(...)]
     *         ),
     *         'else'=>ELSE
     *     )
     * @param array $param
     * @return boolean
     */
    function updateByCase(array $param)
    {
        if ($param) {
            $tmp1 = [];

            foreach ($param as $k1 => $v1) {
                if ($v1 == null) {
                    $tmp1[] = $k1 . '=\'\'';
                } else {
                    if (is_array($v1)) {
                        $a_when = [];

                        foreach ($v1['when'] as $v2) {
                            list ($field, $operator, $value, $then) = $v2;

                            $a_when[] = 'when ' . $field . ' ' . $operator . ' ' . $this->quote($value) . ' then ' . $this->quote($then);
                        }

                        $tmp1[] = $k1 . '= case ' . implode(' ', $a_when) . ' else ' . $v1['else'] . ' end';
                    } else {
                        $tmp1[] = $k1 . '=' . $this->quote($v1);
                    }
                }
            }

            $sql = 'UPDATE ' . \config\database::$Prefix . static::$database . '.' . static::$table . ' set ' . implode(',', $tmp1);

            if (!empty($this->where)) $sql .= ' where ' . implode(' and ', $this->where);

            self::$database_instance[static::$database]->exec($sql);
        }

        if (self::$switch_debug) {
            echo '#[sql]<br>' . $sql . ';<br>';
            self::$switch_debug = false;
        }//debug

        return true;
    }

    function where(array $param)
    {
        foreach ($param as $v_0) {
            if (is_array($v_0)) {
                if (empty($v_0)) continue;

                list ($column, $operator, $value) = $v_0;

                $quote = isset($v_0[3]) ? $v_0[3] : true;

                $string = $this->whereLogic($column, $operator, $value, $quote);
            } else {
                if (trim($v_0) === '') continue;

                $string = $v_0;
            }

            $this->where[] = $string;
        }

        return $this;
    }

    function whereLogic($column, $operator, $value, $quote)
    {
        $string = '';

        switch (strtolower($operator)) {
            case '=':
            case '!=':
            case '>=':
            case '>':
            case '<=':
            case '<':
            case 'is':
            case 'like':
            case 'rlike':
                $string = $column . " " . $operator . " " . $this->quote($value, $quote);
                break;

            case 'between':
                $string = $column . " " . $operator . " " . $this->quote($value[0], $quote) . " AND " . $this->quote($value[1], $quote);
                break;

            case 'in':
                if (empty($value)) {
                    $string = '0 = 1';
                } else {
                    $value = array_unique($value);

                    $string = $column . " " . $operator . " (" . implode(',', array_map([self::$database_instance[static::$database], 'quote'], $value, array_fill(0, count($value), $quote))) . ")";
                }
                break;

            case 'not in':
                if (empty($value)) {
                    $string = '1 = 1';
                } else {
                    $value = array_unique($value);

                    $string = $column . " " . $operator . " (" . implode(',', array_map([self::$database_instance[static::$database], 'quote'], $value, array_fill(0, count($value), $quote))) . ")";
                }
                break;

            default:
                throw new \Exception('Unknown case. "' . $operator . '" of operator.');
                break;
        }

        return $string;
    }
}