<?php

require_once '../vendor/autoload.php';

use miralsoft\weclapp\api\Contact;
use miralsoft\weclapp\api\Config;

require_once 'configWeclapp.php';

$contact = new Contact();
$customer = new \miralsoft\weclapp\api\Customer();

$result = $contact->get(1, 200, '-lastModifiedDate');
//$result = $contact->getAll('customerNumber');

// Gets Customer from contact
$customerFound = 0;
foreach($result as $oContact){
    $contactCustomer = $customer->getCustomerFromContact($oContact);
    $customerFound += count($contactCustomer) > 0 ? 1 : 0;
}
echo '<br><br>';

if (is_array($result)) {
    echo 'Count results: ' . count($result) . ' (founded customers: ' . $customerFound . ')';
}
echo '<br><br>';
print_r($result);
