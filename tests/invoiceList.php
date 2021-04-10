<?php

require_once '../vendor/autoload.php';

use weclapp\api\SalesInvoice;
use weclapp\api\Config;

Config::$URI = 'https://XXX.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'XXX';

$invoice = new SalesInvoice();

$result = $invoice->get(1, 100, \weclapp\api\Sort::DESC);

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);