<?php

namespace lib;

class core
{
    public static $_config;

    function __construct()
    {
        \model\log::setLog();

        /**
         * Model
         */
        model::$switch_memcache = \model\settings::getByKeyword('MEMCACHE');

        /**
         * Controller
         */
        //
        $class = '\\' . \config\module::$Package . '\\' . \config\module::$Class;
        $function = \config\module::$Function;

        (new $class)->$function();

        //
        $class = '\\' . \config\module::$Package . '\Controller';

        $class::display();
    }
}
