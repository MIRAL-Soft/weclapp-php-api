<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\SalesInvoice;
use miralsoft\weclapp\api\Config;
use miralsoft\weclapp\api\Sort;

Config::$URI = 'https://xxx.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'xxx';

$invoice = new SalesInvoice();

$result = $invoice->get(1, 100, Sort::DESC);

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);