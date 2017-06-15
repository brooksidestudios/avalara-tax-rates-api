Avalara Tax Rates API Wrapper
=============================

A simple PHP wrapper for Avalara’s free sales tax rates API.

## AvaTax Developer Account Requirements

You will need to have signed up for an Avalara developer account and API key. More information can be found at  [developer.avalara.com/avatax/signup](https://developer.avalara.com/avatax/signup/).

As of **June 15, 2017**, Avalara has retired the free tax rates API that this api was based on. In order to use (or continue using) this API, you will need to signup for a free trial account which will give you continued access to the free tax rates API.

More details from Avalara:

> *“The trial provides 30 days of full AvaTax product functionality in our Sandbox (testing) environment. This includes continuing support for API access to tax rates. After 30 days, the product trial functionality will be limited to the free tax rates API functionality only.”*

## Install

Using [Composer](http://getcomposer.org/):

```
composer require brookside/avalara-tax-rates-api:dev-master
```

or just require the `TaxRates.php` file:

```php
require 'path/to/TaxRates.php';
```

## Usage

```php
use Brookside\TaxRates\TaxRates;

$tr = new TaxRates([
    'username' => 'YOUR_AVALARA_USERNAME',
    'password' => 'YOUR_AVALARA_PASSWORD',
]);
```

Rates can be retrieved from just a postal code:

```php
$rates = $tr->getRates(74114);
```

For more accurate rates, you can pass as much address information as you have. All available fields are used below:

```php
$rates = $tr->getRates(array(
    'street'  => '4145 E. 21st St',
    'city'    => 'Tulsa',
    'state'   => 'OK',
    'country' => 'USA',
    'postal'  => 74114,
));
```

## Results

Results will vary depending on location, but you will always be returned with an array with two keys: `totalRate` and `rates`. The `rates` key will contain up to three rates – for city, state, and county.

Example result:

> _Note that the new API does not include the actual State, County, or City names like the old API did._

```
Array
(
    [totalRate] => 8.517
    [rates] => Array
        (
            [0] => Array
                (
                    [rate] => 4.5
                    [name] => OK STATE TAX
                    [type] => State
                )

            [1] => Array
                (
                    [rate] => 0.367
                    [name] => OK COUNTY TAX
                    [type] => County
                )

            [2] => Array
                (
                    [rate] => 3.65
                    [name] => OK CITY TAX
                    [type] => City
                )

        )

)
```
