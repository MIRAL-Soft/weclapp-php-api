<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\Customer;
use miralsoft\weclapp\api\Config;

Config::$URI = 'https://xxx.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'xxx';

$customer = new Customer();

//$result = $customer->get(1, 100, 'customerNumber');
$result = $customer->getAll('customerNumber');

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);
