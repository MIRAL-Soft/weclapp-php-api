<?php

require_once '../vendor/autoload.php';

use weclapp\api\SalesInvoice;
use weclapp\api\Config;

Config::$URI = 'https://XXX.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'XXX';


$invoice = new SalesInvoice();

//$result = $invoice->get(1, 2);
$result = $invoice->getInvoicePDF(192777);
header('Content-Disposition: attachment; filename="invoice.pdf"');
header('Content-Type: application/pdf'); # Don't use application/force-download - it's not a real MIME type, and the Content-Disposition header is sufficient
header('Content-Length: ' . strlen($result));
header('Connection: close');

if (is_array($result)) print_r(count($result));
print_r($result);