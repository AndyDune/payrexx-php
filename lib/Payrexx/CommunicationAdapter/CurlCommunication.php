<?php
/**
 * This is the cURL communication adapter
 * @author    Ueli Kramer <ueli.kramer@payrexx.com>
 * @copyright 2015 Payrexx AG
 * @since     v2.0
 */
namespace Payrexx\CommunicationAdapter;

// check for php version 5.2 or higher
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
    throw new \Exception('Your PHP version is not supported. Minimum version should be 5.2.0');
} else if (!function_exists('json_decode')) {
    throw new \Exception('json_decode function missing. Please install the JSON extension');
}

// is the curl extension available?
if (!extension_loaded('curl')) {
    throw new \Exception('Please install the PHP cURL extension');
}

/**
 * Class CurlCommunication for the communication with cURL
 * @package Payrexx\CommunicationAdapter
 */
class CurlCommunication extends \Payrexx\CommunicationAdapter\AbstractCommunication
{
    /**
     * {@inheritdoc}
     */
    public function requestApi($apiUrl, $params = array(), $secret = '', $instance = '', $method = 'POST')
    {
        $curlOpts = array(
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERAGENT => 'payrexx-php/2.0.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CAINFO => dirname(__DIR__) . '/certs/ca.pem',
            CURLOPT_HTTPHEADER => array(
                'PAYREXX-INSTANCE: ' . $instance,
                'PAYREXX-SECRET: ' . $secret,
            )
        );
        if (defined(PHP_QUERY_RFC3986)) {
            $paramString = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        } else {
            // legacy, because the $enc_type has been implemented with PHP 5.4
            $paramString = str_replace(
                array('+', '%7E'),
                array('%20', '~'),
                http_build_query($params, null, '&')
            );
        }
        if ($method == 'GET') {
            if (!empty($params)) {
                $curlOpts[CURLOPT_URL] .= strpos($curlOpts[CURLOPT_URL], '?') === false ? '?' : '&';
                $curlOpts[CURLOPT_URL] .= $paramString;
            }
        } else {
            $curlOpts[CURLOPT_POSTFIELDS] = $paramString;
        }

        $curl = curl_init();
        curl_setopt_array($curl, $curlOpts);
        $responseBody = $this->curlExec($curl);
        $responseInfo = $this->curlInfo($curl);
        var_export($responseBody);
        die();
        
        if ($responseBody === false) {
            $responseBody = array('status' => 'error', 'message' => $this->curlError($curl));
        }
        curl_close($curl);

        if ($responseInfo['content_type'] === 'application/json') {
            $responseBody = json_decode($responseBody, true);
        }

        return array(
            'info' => $responseInfo,
            'body' => $responseBody
        );
    }

    /**
     * The wrapper method for curl_exec
     *
     * @param resource $curl the cURL resource
     *
     * @return mixed
     */
    protected function curlExec($curl)
    {
        return curl_exec($curl);
    }

    /**
     * The wrapper method for curl_getinfo
     *
     * @param resource $curl the cURL resource
     *
     * @return mixed
     */
    protected function curlInfo($curl)
    {
        return curl_getinfo($curl);
    }

    /**
     * The wrapper method for curl_errno
     *
     * @param resource $curl the cURL resource
     *
     * @return mixed
     */
    protected function curlError($curl)
    {
        return curl_errno($curl);
    }
}
