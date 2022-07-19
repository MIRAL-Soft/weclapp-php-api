<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\SalesOrder;
use miralsoft\weclapp\api\Config;

require_once 'configWeclapp.php';

$order = new SalesOrder();

$result = $order->get(1, 100, '-createdDate');

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);