<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\SalesOrder;
use miralsoft\weclapp\api\Config;

Config::$URI = 'https://xxx.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'xxx';

$order = new SalesOrder();

$result = $order->get(1, 100, '-createdDate');

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);