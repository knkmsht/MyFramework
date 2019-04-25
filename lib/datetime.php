<?php

namespace lib;

class datetime
{
    function __construct()
    {
    }

    static function gettime()
    {
        return date('Y-m-d H:i:s');
    }

    static function vernacular($datetime)
    {
        $time = time();
        $timestamp = strtotime($datetime);

        $now = new \DateTime(date('Y-m-d H:i:s', $time));
        $then = new \DateTime(date('Y-m-d H:i:s', $timestamp));

        $diff = $now->diff($then);

        if ($diff->y > 0) {
            $vernacular = $diff->y . ' 年';
        } elseif ($diff->m > 0) {
            $vernacular = $diff->m . ' 個月';
        } elseif ($diff->d > 0) {
            $week = floor($diff->d / 7);

            if ($week > 0) {
                $vernacular = $week . ' 週';
            } else {
                $vernacular = $diff->d . ' 天';
            }
        } elseif ($diff->h > 0) {
            $vernacular = $diff->h . ' 小時';
        } elseif ($diff->i > 0) {
            $vernacular = $diff->i . ' 分';
        } elseif ($diff->s >= 0) {
            $vernacular = $diff->s . ' 秒';
        }

        return $vernacular .= $time >= $timestamp ? '前' : '後';
    }
}
