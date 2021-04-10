<?php

namespace weclapp\api;

class APICall
{
    /** @var object The curl object for this connection */
    protected static $curl = null;

    /**
     * Do a API Call
     *
     * @param string $function The function to call
     * @param array $data The data for this call
     * @param bool $post Is this call a post call
     * @return string The result of the call
     */
    public static function call(string $function, array $data = array(), bool $post = false): string
    {
        self::prepareCall($function, $data, $post);

        // Get the result of curl
        $result = curl_exec(self::$curl);

        // Close the call
        self::closeCall();

        return $result;
    }

    /**
     * Prepare the API Call
     */
    protected static function prepareCall($function, array $data = array(), $post = false)
    {
        $url = Config::$URI . $function . (!$post && count($data) > 0 ? ('?' . http_build_query($data)) : '');
        self::$curl = curl_init($url);
        curl_setopt(self::$curl, CURLOPT_CUSTOMREQUEST, ($post ? "POST" : 'GET'));
        curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);

        // Set Data to curl call
        if ($post) curl_setopt(self::$curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt(self::$curl, CURLOPT_HTTPHEADER, array(
                "AuthenticationToken: " . Config::$TOKEN,
                'Content-Type: application/json')
        );
    }

    /**
     * Close the connection
     */
    protected static function closeCall()
    {
        curl_close(self::$curl);
    }
}