<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\SalesInvoice;
use miralsoft\weclapp\api\Config;

Config::$URI = 'https://xxx.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'xxx';

$invoice = new SalesInvoice();

$result = $invoice->get(1, 100, '-lastModifiedDate');

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);