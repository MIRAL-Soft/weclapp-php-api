<?php


namespace weclapp\api;


class Util
{
    /**
     * Checks the string is a json value
     *
     * @param $string The string to check
     * @return bool true = is a json
     */
    public static function isJson($string) : bool
    {
        if (!is_string($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}