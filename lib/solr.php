<?php

namespace lib;

class solr
{
    private static
        $connection_pool = [];

    protected
        $column = [],
        $limit,
        $order = [],
        $where = [];//q 與 fq 有效能上的處理，參考 http://stackoverflow.com/questions/11627427/solr-query-q-or-filter-query-fq

    function add(array $param)
    {
        if ($param) {
            $doc = new \SolrInputDocument();

            foreach ($param as $k0 => $v0) {
                $doc->addField($k0, $v0);
            }

            $response = self::getSolr()->addDocument($doc);

            self::getSolr()->commit();

            return $response->success();
        }
    }

    function delete()
    {
        $response = self::getSolr()->deleteByQuery(empty($this->where) ? '*:*' : implode(' AND ', $this->where));

        self::getSolr()->commit();

        return $response->success();
    }

    function edit(array $param)
    {
        return $this->add($param);
    }

    function fetch()
    {
        $data = $this->fetch_logic(__FUNCTION__);

        if ($data !== null) {
            $return = $data;
        }

        return $return ?? false;//2017-10-23 Lion: 比照 database, fetch 在沒找到東西時是回傳 boolean false, 故預設 return boolean false
    }

    function fetchAll()
    {
        $data = $this->fetch_logic(__FUNCTION__);

        if ($data !== null) {
            $return = $data;
        }

        return $return ?? [];//2017-10-23 Lion: 比照 database, fetchAll 在沒找到東西時是回傳空陣列, 故預設 return  []
    }

    function fetch_logic($fetch_case)
    {
        $query = new \SolrQuery();

        $query->setQuery('*:*');

        if (!empty($this->column)) foreach ($this->column as $v0) $query->addField($v0);

        if (!empty($this->where)) $query->addFilterQuery(implode(' AND ', $this->where));

        if (!empty($this->order)) foreach ($this->order as $k0 => $v0) {
            $order = $v0 == 'asc' ? $query::ORDER_ASC : $query::ORDER_DESC;
            $query->addSortField($k0, $order);
        }

        if (empty($this->limit)) {
            $start = 0;

            $array = explode('_', static::$core);

            $string = '\database\\' . end($array);

            $rows = (new $string)->select(['COUNT(1)'])->fetchColumn();
        } else {
            list ($start, $rows) = explode(',', $this->limit);
        }

        $query->setStart($start)->setRows($rows);

        $data = null;

        try {
            $query_response = self::getSolr()->query($query);

            $sql = $query_response->getRequestUrl() . '&' . $query_response->getRawRequest();

            $response = $query_response->getResponse();

            if ($response->response->docs) {
                switch ($fetch_case) {
                    case 'fetch':
                        $data = (array)$response->response->docs[0];
                        break;

                    case 'fetchAll':
                        foreach ($response->response->docs as $v0) {
                            $data[] = (array)$v0;
                        }
                        break;
                }
            }
        } catch (\Exception $exception) {
            \model\log::setException(\lib\exception::LEVEL_NOTICE, $exception->getMessage());
        }

        return $data;
    }

    public static function getSolr()
    {
        if (!isset(self::$connection_pool[static::$core])) {
            if (!class_exists('\SolrClient')) {
                \model\log::setException(\lib\exception::LEVEL_ERROR, 'Not found : Class of [\SolrClient] in ' . __METHOD__);
            }

            self::$connection_pool[static::$core] = new \SolrClient([
                'hostname' => 'localhost',
                'path' => 'solr/' . static::$core,
                'port' => 8983,
                'wt' => 'json',
            ]);
        }

        return self::$connection_pool[static::$core];
    }

    function limit($limit)
    {
        if ($limit) $this->limit = str_replace(' ', '', $limit);

        return $this;
    }

    function order(array $order)
    {
        if ($order) {
            foreach ($order as $k0 => $v0) {
                $this->order[trim($k0)] = strtolower(trim($v0));
            }
        }

        return $this;
    }

    static function quote($value, $quote = true)
    {
        return ($quote) ? \SolrUtils::escapeQueryChars($value) : $value;
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

    function where(array $param)
    {
        foreach ($param as $v_0) {
            if (is_array($v_0)) {
                if (empty($v_0)) continue;

                list ($column, $operator, $value) = $v_0;

                $quote = isset($v_0[3]) ? $v_0[3] : true;

                switch (strtolower($operator)) {
                    case '=':
                        $string = $column . ':' . self::quote($value, $quote);
                        break;

                    case '!=':
                        $string = '-' . $column . ':' . self::quote($value, $quote);
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