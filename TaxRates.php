<?php
namespace Brookside\TaxRates;

/**
 * Avalara Tax Rates API Wrapper
 *
 * @copyright Copyright (c) Brookside Studios
 * @link      https://github.com/brooksidestudios/avalara-tax-rates-api
 * @version   1.1.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
class TaxRates
{
    /**
     * The API base URL
     */
    const API_URL = 'https://sandbox-rest.avatax.com/api/v2/taxrates/';

    /**
     * Default country
     */
    const COUNTRY = 'US';

    /**
     * Error messages
     */
    const ERROR_400A    = 'The postal code you provided was not valid. Please check your postal code before re-trying.';
    const ERROR_400B    = 'One of the values you submitted was empty or the postal code you provided was not valid.';
    const ERROR_401     = 'Your authorization credentials were not provided or were invalid.';
    const ERROR_429     = 'Rate limiting has been exceeded. Try again later.';
    const ERROR_UNKNOWN = 'Unknown error with the tax rates api. Try again later.';

    /**
     * Available actions
     *
     * @var array
     */
    private $_actions = array(
        'bypostalcode',
        'byaddress',
    );

    /**
     * Available parameters
     *
     * @var array
     */
    private $_params = array(
        'line1',
        'line2',
        'line3',
        'city',
        'region',
        'postalCode',
        'country',
    );

    /**
     * Required fields for "byaddress" endpoint
     *
     * @var array
     */
    private $_required = array(
        'line1',
        'city',
        'region',
        'postalCode',
    );

    /**
     * Default constructor
     *
     * @param  array     $creds
     * @throws Exception
     * @return void
     */
    public function __construct($creds = array())
    {
        if ( ! isset($creds['username'])) {
            throw new \Exception('You must include a username');
        } elseif ( ! isset($creds['password'])) {
            throw new \Exception('You must include a password');
        } else {
            $this->_creds = $creds;
        }
    }

    /**
     * Retrieves a set of tax rates for a given address or postal code
     *
     * @param  array|string $params
     * @param  string       $action byaddress|bypostalcode
     * @throws Exception
     * @return array
     */
    public function getRates($params = array(), $action = null)
    {
        if (is_string($params) || is_numeric($params)) {
            $params = array('postalCode' => $params);
        }

        if ( ! isset($params['country'])) {
            $params['country'] = self::COUNTRY;
        }

        if (strlen($params['country']) != 2) {
            throw new \Exception('Country must be a two letter ISO-3166 country code');
        }

        // Attempt to re-map fields
        $params = $this->_remapParams($params);

        // Attempt to get action from passed parameters
        $action = (isset($action) && in_array($action, $this->_getActions()) ? $action : $this->_getActionFromParams($params));

        if ( ! $this->_hasRequiredParamsForAction($action, $params)) {
            throw new \Exception('You are missing one or more required parameters.');
        }

        return $this->_makeCall($action, $params);
    }

    /**
     * Available actions getter
     *
     * @return array
     */
    private function _getActions()
    {
        return $this->_actions;
    }

    /**
     * Attempt to remap fields from previous version of Avalara's API
     *
     * @param  array $params
     * @return array
     */
    private function _remapParams($params = array())
    {
        $map = array(
            'street' => 'line1',
            'state'  => 'region',
            'postal' => 'postalCode',
        );

        foreach ($map as $old => $new) {
            if (isset($params[$old])) {
                $params[$new] = $params[$old];
            }

            unset($params[$old]);
        }

        return $params;
    }

    /**
     * Attempts to get the proper action from parameters passed
     *
     * @param  array  $params
     * @return string
     */
    private function _getActionFromParams($params = array())
    {
        foreach ($this->_required as $param) {
            if ( ! isset($params[$param])) {
                return 'bypostalcode';
            }
        }

        return 'byaddress';
    }

    /**
     * Checks for required fields for a given action
     *
     * @param  string $action
     * @param  array  $params
     * @return bool
     */
    private function _hasRequiredParamsForAction($action, $params)
    {
        if ($action == 'byaddress') {
            foreach ($this->_required as $param) {
                if ( ! isset($params[$param])) {
                    return false;
                }
            }
        } else {
            if ( ! isset($params['country'], $params['postalCode'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Method that makes the actual call to the API endpoint
     *
     * @param  string    $action
     * @param  array     $params
     * @throws Exception
     * @return array
     */
    private function _makeCall($action, $params = array())
    {
        $paramString = null;

        if (isset($params) && is_array($params)) {
            $paramString = '?' . http_build_query($params);
        }

        $apiCall = self::API_URL . $action . $paramString;

        $ch = curl_init();

        curl_setopt_array($ch, array(
            CURLOPT_USERPWD        => "{$this->_creds['username']}:{$this->_creds['password']}",
            CURLOPT_URL            => $apiCall,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ));

        $jsonData = curl_exec($ch);

        if (curl_errno($ch) || ! $jsonData) {
            throw new \Exception('Error: _makeCall() - cURL error: ' . curl_error($ch));
        }

        $info = curl_getinfo($ch);

        if (preg_match('/^4/', $info['http_code'])) {
            switch ($info['http_code']) {
                case 400:
                    if ($action == 'byaddress') {
                        $error = self::ERROR_400B;
                    } else {
                        $error = self::ERROR_400A;
                    }
                    break;

                case 401:
                    $error = self::ERROR_401;
                    break;

                case 429:
                    $error = self::ERROR_429;
                    break;

                default:
                    $error = self::ERROR_UNKNOWN;
                    break;
            }

            throw new \Exception($error);
        }

        curl_close($ch);

        // Decode result
        $result = json_decode($jsonData, true);

        // Convert rates to match the previous API functionality
        $result['totalRate'] = (100 * $result['totalRate']);

        foreach ($result['rates'] as $key => $value) {
            $result['rates'][$key]['rate'] = (100 * $value['rate']);
        }

        return $result;
    }
}
