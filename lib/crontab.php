<?php

namespace lib;

class crontab
{
    private
        $format = 'Y-m-d H:i:s',
        $instance;

    function __construct($expression)
    {
        $this->instance = \Cron\CronExpression::factory($expression);
    }

    function getNextRunDate()
    {
        return $this->instance->getNextRunDate()->format($this->format);
    }

    function isDue(): bool
    {
        return $this->instance->isDue();
    }
}
