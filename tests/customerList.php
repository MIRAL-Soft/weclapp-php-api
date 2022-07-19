<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\Customer;
use miralsoft\weclapp\api\Config;

require_once 'configWeclapp.php';

$customer = new Customer();

//$result = $customer->get(1, 100, 'customerNumber');
$result = $customer->getAll('customerNumber');

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);
