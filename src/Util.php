<?php


namespace miralsoft\weclapp\api;


class Util
{
    /**
     * Checks the string is a json value
     *
     * @param $string The string to check
     * @return bool true = is a json
     */
    public static function isJson($string): bool
    {
        if (!is_string($string)) return false;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Checks the available of the given url
     *
     * @param $url The url to test
     * @return bool true = site is available | false = not available
     */
    public static function isSiteAvailable($url): bool
    {
        // Initialize cURL
        $curl = curl_init($url);

        // Set the option to not return the content
        curl_setopt($curl, CURLOPT_NOBODY, true);

        // Set a timeout for the request in case the server takes too long to respond
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        // Set a user agent (some servers do not respond without a legitimate user agent)
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');

        // Perform the request
        $result = curl_exec($curl);

        // Get the HTTP status code
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // Close the cURL session
        curl_close($curl);

        // Check if the request was successful
        return ($result && ($statusCode >= 200) && ($statusCode < 400));
    }
}