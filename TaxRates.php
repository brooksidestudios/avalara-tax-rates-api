<?php
namespace Brookside\TaxRates;

/**
 * Avalara Tax Rates API Wrapper
 *
 * @copyright Copyright (c) Brookside Studios
 * @link      https://github.com/brooksidestudios/avalara-tax-rates-api
 * @version   1.0.1
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
class TaxRates
{

    /**
     * The API base URL
     */
    const API_URL = 'https://taxrates.api.avalara.com:443/';

    /**
     * Default country
     */
    const COUNTRY = 'USA';

    /**
     * Error messages
     */
    const ERROR_400A    = 'The postal code you provided was not valid. Please check your postal code before re-trying.';
    const ERROR_400B    = 'One of the values you submitted was empty or the postal code you provied was not valid.';
    const ERROR_401     = 'Your authorization credentials were not provided or were invalid.';
    const ERROR_429     = 'Rate limiting has been exceeded. Try again later.';
    const ERROR_UNKNOWN = 'Unknown error with the tax rates api. Try again later.';

    /**
     * The Avalara Tax Rates API key
     *
     * @var string
     */
    private $_apikey;

    /**
     * Available actions
     *
     * @var string
     */
    private $_actions = array('postal', 'address');

    /**
     * Available parameters
     *
     * @var string
     */
    private $_params = array('street', 'city', 'state', 'country', 'postal');

    /**
     * Default constructor
     *
     * @param string $apiKey
     * @return void
     * @throws Exception
     */
    public function __construct($apiKey = null)
    {
        if (isset($apiKey)) {
            $this->setApiKey($apiKey);
        } else {
            throw new \Exception('Missing API key');
        }
    }

    /**
     * Retrieves a set of tax rates for a given address or postal code
     *
     * @param array|string $params
     * @param string $action address|postal
     * @return array
     * @throws Exception
     */
    public function getRates($params = array(), $action = null)
    {
        if (is_string($params) || is_numeric($params)) {
            $params = array('postal' => $params);
        }

        if ( ! isset($params['country'])) {
            $params['country'] = self::COUNTRY;
        }

        $action = (isset($action) && in_array($action, $this->getActions()) ? $action : $this->getActionFromParams($params));

        if ( ! $this->hasRequiredParamsForAction($action, $params)) {
            throw new \Exception('You are missing one or more required parameters.');
        }

        return $this->_makeCall($action, $params);
    }

    /**
     * API key setter
     *
     * @param string $apiKey
     * @return void
     */
    private function setApiKey($apiKey)
    {
        $this->_apikey = $apiKey;
    }

    /**
     * API key getter
     *
     * @return string Api Key
     */
    private function getApiKey()
    {
        return $this->_apikey;
    }

    /**
     * Available actions getter
     *
     * @return array
     */
    private function getActions()
    {
        return $this->_actions;
    }

    /**
     * Attempts to get the proper action from parameters passed
     *
     * @param array $params
     * @return string
     */
    private function getActionFromParams($params = array())
    {
        foreach ($this->_params as $param) {
            if ( ! isset($params[$param])) {
                return 'postal';
            }
        }

        return 'address';
    }

    /**
     * Checks for required fields for a given action
     *
     * @param string $action
     * @param array $params
     * @return boolean
     */
    private function hasRequiredParamsForAction($action, $params)
    {
        if ($action == 'address') {
            foreach ($this->_params as $param) {
                if ( ! isset($params[$param])) {
                    return false;
                }
            }
        } else {
            if ( ! isset($params['country'], $params['postal'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Method that makes the actual call to the API endpoint
     *
     * @param string $action
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function _makeCall($action, $params = array())
    {
        if ( ! isset($this->_apikey)) {
            throw new \Exception('Invalid api key');
        }

        $authMethod = '?apikey=' . urlencode($this->getApiKey());

        $paramString = null;

        if (isset($params) && is_array($params)) {
            $paramString = '&' . http_build_query($params);
        }

        $apiCall = self::API_URL . $action . $authMethod . $paramString;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiCall,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $jsonData = curl_exec($ch);

        if (curl_errno($ch) || ! $jsonData) {
            throw new \Exception('Error: _makeCall() - cURL error: ' . curl_error($ch));
        }

        $info = curl_getinfo($ch);

        if (preg_match('/^4/', $info['http_code'])) {
            switch ($info['http_code']) {
                case 400:
                    if ($action == 'address') {
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

        return json_decode($jsonData, true);
    }

}
