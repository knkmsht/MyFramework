<?php

namespace lib;

class database
{
    private static
        $connection_pool = [],
        $debug_switch = false,
        $transaction_pool = [],
        $transaction_switch = false;

    protected
        $column = [],
        $join = [],
        $where = [],
        $group = [],
        $having = [],
        $order = [],
        $limit,
        $lock;

    static function beginTransaction()
    {
        if (self::$transaction_switch === false) {
            self::$transaction_switch = true;
        }
    }

    /**
     * 檢查交易
     */
    private static function checkTransaction()
    {
        if (self::$transaction_switch) {
            if (!array_key_exists(static::$database, self::$transaction_pool)) {
                self::$transaction_pool[static::$database] = self::getDatabase();

                self::$transaction_pool[static::$database]->beginTransaction();
            }
        }
    }

    static function commit()
    {
        if (self::$transaction_switch === true) {
            foreach (self::$transaction_pool as $database => $connection) {
                $connection->commit();

                unset(self::$transaction_pool[$database]);
            }

            self::$transaction_switch = false;
        }
    }

    function debug()
    {
        self::$debug_switch = true;

        return $this;
    }

    function delete()
    {
        $sql = 'DELETE FROM ' . static::$table;

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . implode(' ', $this->where);
        }

        self::checkTransaction();

        self::getDatabase()->exec($sql);

        if (self::$debug_switch) {
            echo '#[sql]<br>' . $sql . ';<br>';
            self::$debug_switch = false;
        }//debug
    }

    function fetch()
    {
        return $this->fetchLogic(__FUNCTION__);
    }

    function fetchAll()
    {
        return $this->fetchLogic(__FUNCTION__);
    }

    function fetchColumn()
    {
        return $this->fetchLogic(__FUNCTION__);
    }

    static function fetchEnum($field): array
    {
        preg_match("/^enum\(\'(.*)\'\)$/", self::getDatabase()->fetch('SHOW COLUMNS FROM ' . static::$table . ' WHERE FIELD = ' . self::quote($field))['Type'], $matches);

        return $matches ? explode("','", $matches[1]) : [];
    }

    private function fetchLogic($fetch_case)
    {
        $sql = 'SELECT';

        $sql .= ($this->column) ? ' ' . implode(',', $this->column) : ' ' . static::$table . '.*';

        $sql .= ' FROM ' . static::$table;

        if (!empty($this->join)) $sql .= ' ' . implode(' ', $this->join);

        if ($this->where) $sql .= ' WHERE ' . implode(' ', $this->where);

        if ($this->group) $sql .= ' GROUP BY ' . implode(',', $this->group);

        if ($this->having) $sql .= ' HAVING ' . implode(' ', $this->having);

        if (!empty($this->order)) $sql .= ' ORDER BY ' . implode(',', $this->order);

        if (!empty($this->limit)) $sql .= ' LIMIT ' . $this->limit;

        if (!empty($this->lock)) $sql .= ' ' . $this->lock;

        if (self::$debug_switch) {
            echo '#[sql]<br>' . $sql . ';<br>';
            self::$debug_switch = false;
        }//debug

        self::checkTransaction();

        return self::getDatabase()->$fetch_case($sql);
    }

    static function getConnectionId()
    {
        return self::getDatabase()->fetchColumn('SELECT CONNECTION_ID()');
    }

    public static function getDatabase()
    {
        if (!isset(self::$connection_pool[static::$database])) {
            self::$connection_pool[static::$database] = new \lib\databaseabstract(\config\database::settings()[static::$database]);
        }

        return self::$connection_pool[static::$database];
    }

    function group(array $group)
    {
        if ($group) {
            $group = array_filter($group, function ($v_0) {
                return $v_0 !== null;
            });

            $this->group = array_merge($this->group, $group);
        }

        return $this;
    }

    function having(array $param)
    {
        foreach ($param as $v_0) {
            if (is_array($v_0)) {
                if (empty($v_0)) continue;

                list ($column, $operator, $value) = $v_0;

                $quote = $v_0[3] ?? true;

                switch (strtolower($operator)) {
                    case '=':
                        $string = $column . ' ' . $operator . ' ' . self::quote($value, $quote);
                        break;

                    default:
                        \model\log::setException(\lib\exception::LEVEL_ERROR, 'Unknown case. "' . $operator . '" of operator.');
                        break;
                }
            } else {
                if (trim($v_0) === '') continue;

                $string = $v_0;
            }

            $this->having[] = $string;
        }

        return $this;
    }

    function insert(array $insert)
    {
        return $this->insertLogic($insert);
    }

    function insertIgnore(array $insert)
    {
        return $this->insertLogic($insert, true);
    }

    private function insertLogic(array $insert, $ignore = false, $onduplicatekeyupdate = false)
    {
        $id = 0;//沒有 AUTO_INCREMENT 或是寫入失敗會得到 0

        if ($insert) {
            switch (array_depth($insert)) {
                case 1:
                    $a_column = array_keys($insert);
                    $s_value = '(' . implode(',', array_map([self::getDatabase(), 'quote'], $insert)) . ')';
                    break;

                case 2:
                    $a_column = array_keys(reset($insert));
                    $a_value = [];

                    foreach ($insert as $v_0) {
                        $a_value[] = '(' . implode(',', array_map([self::getDatabase(), 'quote'], $v_0)) . ')';
                    }

                    $s_value = implode(',', $a_value);
                    break;
            }

            $sql = 'INSERT ' . ($ignore ? 'IGNORE' : null) . ' INTO ' . static::$table . ' (' . implode(',', $a_column) . ') VALUES ' . $s_value;

            if ($onduplicatekeyupdate) {
                $array_0 = [];

                foreach ($a_column as $v_0) {
                    $array_0[] = $v_0 . '=VALUES(' . $v_0 . ')';
                }

                $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(',', $array_0);
            }

            if (self::$debug_switch) {
                echo '#[sql]<br>' . $sql . ';<br>';
                self::$debug_switch = false;
            }//debug

            self::checkTransaction();

            self::getDatabase()->exec($sql);

            $id = (int)self::getDatabase()->lastInsertId();
        }

        return $id;
    }

    function insertOnDuplicateKeyUpdate(array $insert)
    {
        return $this->insertLogic($insert, false, true);
    }

    function join(array $join)
    {
        if ($join) {
            $join = array_filter($join, function ($v_0) {
                return trim($v_0) !== '';
            });

            $this->join = array_merge($this->join, $join);
        }

        return $this;
    }

    static function kill($connection_id)
    {
        self::getDatabase()->exec('KILL ' . $connection_id);
    }

    function limit($limit)
    {
        if (trim($limit) !== '') $this->limit = str_replace(' ', '', $limit);

        return $this;
    }

    function lockForUpdate()
    {
        $this->lock = 'for update';

        return $this;
    }

    function order(array $order)
    {
        if ($order) {
            foreach ($order as $k_0 => $v_0) {
                $this->order[] = trim($k_0) . ' ' . trim($v_0);
            }
        }

        return $this;
    }

    static function quote($value, $quote = true)
    {
        return self::getDatabase()->quote($value, $quote);
    }

    static function rollBack()
    {
        if (self::$transaction_switch === true) {
            foreach (self::$transaction_pool as $database => $connection) {
                $connection->rollBack();

                unset(self::$transaction_pool[$database]);
            }

            self::$transaction_switch = false;
        }
    }

    function select(array $select)
    {
        if ($select) {
            $select = array_filter($select, function ($v_0) {
                return trim($v_0) !== '';
            });

            $this->column = array_merge($this->column, $select);
        }

        return $this;
    }

    /**
     * TRUNCATE TABLE 會導致隱式提交，改用 DELETE FROM
     */
    static function truncate()
    {
        self::checkTransaction();

        self::getDatabase()->exec('DELETE FROM ' . static::$table);
    }

    function update(array $update)
    {
        if ($update) {
            $array_0 = [];

            foreach ($update as $k_0 => $v_0) {
                $quote = true;

                if (is_array($v_0)) list ($v_0, $quote) = $v_0;

                $array_0[] = $k_0 . '=' . self::quote($v_0, $quote);
            }

            $sql = 'UPDATE ' . static::$table . ' SET ' . implode(',', $array_0);

            if (!empty($this->where)) {
                $sql .= ' WHERE ' . implode(' ', $this->where);
            }

            if (self::$debug_switch) {
                echo '#[sql]<br>' . $sql . ';<br>';
                self::$debug_switch = false;
            }//debug

            self::checkTransaction();

            self::getDatabase()->exec($sql);
        }
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
     */
    function updateByCase(array $param)
    {
        if ($param) {
            $array_0 = [];

            foreach ($param as $k_0 => $v_0) {
                if ($v_0 == null) {
                    $array_0[] = $k_0 . '=\'\'';
                } else {
                    if (is_array($v_0)) {
                        $a_when = [];

                        foreach ($v_0['when'] as $v_1) {
                            list ($field, $operator, $value, $then) = $v_1;

                            $a_when[] = 'WHEN ' . $field . ' ' . $operator . ' ' . self::quote($value) . ' THEN ' . self::quote($then);
                        }

                        $array_0[] = $k_0 . '= CASE ' . implode(' ', $a_when) . ' ELSE ' . $v_0['else'] . ' END';
                    } else {
                        $array_0[] = $k_0 . '=' . self::quote($v_0);
                    }
                }
            }

            $sql = 'UPDATE ' . static::$table . ' SET ' . implode(',', $array_0);

            if (!empty($this->where)) $sql .= ' WHERE ' . implode(' AND ', $this->where);

            if (self::$debug_switch) {
                echo '#[sql]<br>' . $sql . ';<br>';
                self::$debug_switch = false;
            }//debug

            self::checkTransaction();

            self::getDatabase()->exec($sql);
        }
    }

    function where(array $param)
    {
        foreach ($param as $v_0) {
            if (is_array($v_0)) {
                if (empty($v_0)) continue;

                list ($column, $operator, $value) = $v_0;

                $quote = isset($v_0[3]) ? $v_0[3] : true;

                switch (strtolower($operator)) {
                    case '=':
                    case '!=':
                    case '>=':
                    case '>':
                    case '<=':
                    case '<':
                    case 'is':
                    case 'is not':
                    case 'like':
                    case 'rlike':
                        $string = $column . ' ' . $operator . ' ' . self::quote($value, $quote);
                        break;

                    case 'between':
                        $string = $column . " " . $operator . " " . self::quote($value[0], $quote) . " AND " . self::quote($value[1], $quote);
                        break;

                    case 'in':
                        if (empty($value)) {
                            $string = '0 = 1';
                        } else {
                            $value = array_unique($value);

                            $string = $column . " " . $operator . " (" . implode(',', array_map([self::getDatabase(), 'quote'], $value, array_fill(0, count($value), $quote))) . ")";
                        }
                        break;

                    case 'not in':
                        if (empty($value)) {
                            $string = '1 = 1';
                        } else {
                            $value = array_unique($value);

                            $string = $column . " " . $operator . " (" . implode(',', array_map([self::getDatabase(), 'quote'], $value, array_fill(0, count($value), $quote))) . ")";
                        }
                        break;

                    default:
                        \model\log::setException(\lib\exception::LEVEL_ERROR, 'Unknown case. "' . $operator . '" of operator.');
                        break;
                }
            } else {
                if (trim($v_0) === '') continue;

                $string = $v_0;
            }

            $this->where[] = $string;
        }

        return $this;
    }
}
