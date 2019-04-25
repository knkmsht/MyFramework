<?php

namespace lib;

class schema
{
    const
        default = 'default',
        length = 'length',
        name = 'name',
        null = 'null';

    static function checkLogic(array $schema, $value)
    {
        list (, $logic) = self::decode($schema);

        return $logic($value);
    }

    static function checkRequired(array $schema, $value)
    {
        $check = function (array $schema, $value) {
            list ($name) = self::decode($schema);

            if (replaceSpace($value) === '') {
                $result = \lib\result::SYSTEM_ERROR;
                $message = 'Param error, "' . $name . '" is required.';

                goto _return;
            }

            $result = \lib\result::SYSTEM_OK;
            $message = null;

            _return:

            return return_encode($result, $message);
        };

        if (is_array($value)) {
            foreach ($value as $v_0) {
                list ($result, $message) = return_decode($check($schema, $v_0));
                if ($result != \lib\result::SYSTEM_OK) goto _return;
            }
        } else {
            list ($result, $message) = return_decode($check($schema, $value));
            if ($result != \lib\result::SYSTEM_OK) goto _return;
        }

        $result = \lib\result::SYSTEM_OK;
        $message = null;

        _return:

        return return_encode($result, $message);
    }

    static function decode(array $array)
    {
        return [
            $array['name'],
            $array['logic'],
            $array['format'],
        ];
    }

    static function format(array $schema, $value)
    {
        list (, , $format) = self::decode($schema);

        return $format($value);
    }

    static function return($return, $options = null)
    {
        if ($options !== null) {
            switch ($options) {
                case self::default:
                    $return = $return[1];
                    break;

                case self::length:
                    $return = $return[0];
                    break;
            }
        }

        return $return;
    }

    static function encode($name, $logic = null, $format = null)
    {
        return [
            'name' => $name,
            'logic' => $logic,
            'format' => $format,
        ];
    }
}