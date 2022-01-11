# weclapp-php-api
This Project is to use the weclapp API with PHP.

# How to use
You can download the Package over composer with following line in composer File:

```
"require": {
    "php": ">=7.4.0",<br>
    "miralsoft/weclapp-api": ">=v1"
}
```

# Configuration
The configuration have to been set in your PHP-Project. You must define 2 constants like this:

```
use miralsoft\weclapp\api\Config;

Config::$URI = 'https://xxx.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'xxx';
```

Replace the xxx with your own data.

# Full example
To get a list of customers, here is a example:

```
use miralsoft\weclapp\api\Customer;
use miralsoft\weclapp\api\Config;

Config::$URI = 'https://xxx.weclapp.com/webapp/api/v1/';
Config::$TOKEN = 'xxx';

$customer = new Customer();

$result = $customer->get(1, 100, 'customerNumber');

if (is_array($result)) {
    echo 'Count results: ' . count($result);
}
echo '<br><br>';
print_r($result);
```

You get a list of first 100 customers and can use it in any way.