Avalara Tax Rates API Wrapper
=============================

A simple PHP wrapper for Avalara’s free sales tax rates API.

## Install

Using [Composer](http://getcomposer.org/):

`composer require brookside/avalara-tax-rates-api:dev-master`

or just require the `TaxRates.php` file:

```php
require 'path/to/TaxRates.php';
```

## Usage

> **Note:** You will need to have signed up for an Avalara developer account and API key. More information [can be found at taxratesapi.avalara.com](http://taxratesapi.avalara.com).

```php
use Brookside\TaxRates\TaxRates;

$tr = new TaxRates('YOUR_AVALARA_API_KEY');
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

```
Array
(
    [totalRate] => 8.517
    [rates] => Array
        (
            [0] => Array
                (
                    [rate] => 3.1
                    [name] => TULSA
                    [type] => City
                )

            [1] => Array
                (
                    [rate] => 4.5
                    [name] => OKLAHOMA
                    [type] => State
                )

            [2] => Array
                (
                    [rate] => 0.917
                    [name] => TULSA
                    [type] => County
                )

        )

)
```
