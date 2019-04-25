<?php
/**
 * Map 處理器
 * <p>v1.0 2016-11-08</p>
 * @author lion
 */

namespace lib;

class Map
{
    static function getCoordinate($address)
    {
        $return = [null, null];

        if (trim($address) !== '') {
            $address = str_replace(' ', '+', $address);// replace all the white space with "+" sign to match with google search pattern

            $results = json_decode(file_get_contents('http://maps.google.com/maps/api/geocode/json?address=' . $address), true)['results'];

            if (isset($results[0])) {
                $location = $results[0]['geometry']['location'];

                $return = [$location['lat'], $location['lng']];
            }
        }

        return $return;
    }
}