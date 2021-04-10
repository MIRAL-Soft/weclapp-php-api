<?php


namespace miralsoft\weclapp\api;


use ReflectionClass;

abstract class WeclappAPICall
{
    /** @var string The main function from the call */
    protected string $mainFunction = '';

    /** @var string The sub function from the call */
    protected string $subFunction = '';

    /**
     * WeclappAPICall constructor.
     */
    public function __construct()
    {
        $reflect = new ReflectionClass($this);
        $this->mainFunction = lcfirst($reflect->getShortName()) . '/';
    }

    /**
     * Set the sub function of this call
     * @param string $subFunction The subfunction
     */
    public function setSubFunction(string $subFunction)
    {
        $this->subFunction = $subFunction;
    }

    /**
     * Do the API Call
     *
     * @param array $data The data for this call
     * @param bool $post true = POST | false = GET
     * @return array The return value of the api call
     */
    protected function call(array $data = array(), bool $post = false, bool $formateResult = true)
    {
        $result = APICall::call($this->mainFunction . $this->subFunction, $data, $post);

        if ($formateResult) {
            // If the result is a json encode it
            if (Util::isJson($result)) $result = json_decode($result, true);
            // If no json give a empty array
            else $result = array();

            return count($result) > 0 && isset($result['result']) ? $result['result'] : $result;
        }

        return $result;
    }
}