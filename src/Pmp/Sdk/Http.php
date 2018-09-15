<?php
namespace Pmp\Sdk;

use \GuzzleHttp\Client;
use \GuzzleHttp\Exception\GuzzleException;
use \GuzzleHttp\Exception\RequestException;

/**
 * PMP common HTTP utils
 *
 * Methods to help abstract out some common guzzle setup/usage
 *
 */
class Http
{
    const CONTENT_TYPE              = 'application/vnd.collection.doc+json';
    const USER_AGENT_PREFIX         = 'phpsdk/v';
    const TIMEOUT_S                 = 5;

    // global http-request options
    static protected $optGzip    = true;
    static protected $optMinimal = true;

    /**
     * Set advanced options for http requests
     *
     * @param array $opts the options to set
     */
    static public function setOptions($opts = array()) {
        if (isset($opts['gzip'])) {
            self::$optGzip = $opts['gzip'] ? true : false;
        }
        if (isset($opts['minimal'])) {
            self::$optMinimal = $opts['minimal'] ? true : false;
        }
    }

    /**
     * Make a normal bearer-auth request
     *
     * @param string $method the http method
     * @param string $url the absolute location
     * @param string $token the auth token
     * @param array $data optional body data
     * @return array($status, $jsonObj, $rawData) the response status and body
     */
    static public function bearerRequest($method, $url, $token = null, $data = null) {
        $opts = [
            'headers' => [
                'User-Agent' => self::USER_AGENT_PREFIX . \Pmp\Sdk::VERSION,
                'Accept' => self::CONTENT_TYPE,
                'Content-Type' => self::CONTENT_TYPE,
            ]
        ];

        if (self::$optGzip) {
            $opts['headers']['Accept-Encoding'] = 'gzip,deflate';
        }
        if ($token) {
            $opts['headers']['Authorization'] = "Bearer $token";
        }
        if ((strtolower($method) == 'post' || strtolower($method) == 'put') && !empty($data)) {
            $opts['body'] = json_encode($data);
        }

        // preferences - only agree to minimal responses for non-home-docs
        $path = parse_url($url, PHP_URL_PATH);
        if (self::$optMinimal && !empty($path)) {
            $opts['headers']['Prefer'] =  'return=minimal';
        }

        return self::_sendRequest($method, $url, $opts);
    }

    /**
     * Make a basic-auth'd request to the auth API
     *
     * @param string $method the http method
     * @param string $url the absolute location
     * @param string $basicAuth the basic auth string
     * @param array $postData optional POST data
     * @return array($status, $jsonObj, $rawData) the response status and body
     */
    static public function basicRequest($method, $url, $basicAuth, $postData = null) {
        $opts = [
            'headers' => [
                'User-Agent' => self::USER_AGENT_PREFIX . \Pmp\Sdk::VERSION,
                'Accept' => 'application/json',
                'Authorization' => $basicAuth,
            ]
        ];
        if (strtolower($method) == 'post' && !empty($postData)) {
            $opts['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            $formParams = [];
            foreach ($postData as $key => $value) {
                if ($value) {
                    $formParams[$key] = $value;
                }
            }
            $opts['form_params'] = $formParams;
        }

        return self::_sendRequest($method, $url, $opts);
    }

    /**
     * Send a request and handle the response
     *
     * @param string $method the http method
     * @param string $url the absolute location
     * @param array $opts the request options
     * @return array($status, $jsonObj, $rawData) the response status and body
     */
    static private function _sendRequest($method, $url, $opts) {
        $client = new Client();
        $opts['timeout'] = self::TIMEOUT_S;
        $err_data = array('method' => $method, 'url' => $url);

        // make the request, catching guzzle errors
        try {
            $resp = $client->request($method, $url, $opts);
        }
        catch (RequestException $e) {
            $resp = $e->getResponse();
        }
        catch (GuzzleException $e) {
            throw new Exception\RemoteException('Unable to complete request', $err_data);
        }
        $code = $resp->getStatusCode();
        $body = $resp->getBody();
        $json = json_decode($body);
        $err_data['code'] = $code;
        $err_data['body'] = "$body";

        // debug logger
        if (getenv('DEBUG') == '1' || getenv('DEBUG') == '2') {
            echo "# $code $method $url\n";
        }
        if (getenv('DEBUG') == '2') {
            echo "  $body\n";
        }

        // handle bad response data
        if ($code != 204 && empty($body)) {
            throw new Exception\RemoteException('Empty Document', $err_data);
        }
        else if ($code == 401) {
            throw new Exception\AuthException('Unauthorized', $err_data);
        }
        else if ($code == 403) {
            throw new Exception\NotFoundException('Forbidden', $err_data);
        }
        else if ($code == 404) {
            throw new Exception\NotFoundException('Not Found', $err_data);
        }
        else if ($code < 200) {
            throw new Exception\RemoteException('Informational', $err_data);
        }
        else if ($code > 299 && $code < 400) {
            throw new Exception\RemoteException('Redirection', $err_data);
        }
        else if ($code > 399 && $code < 500) {
            throw new Exception\RemoteException('Client Error', $err_data);
        }
        else if ($code > 499) {
            throw new Exception\RemoteException('Server Error', $err_data);
        }
        else if ($code != 204 && is_null($json) && json_last_error() != JSON_ERROR_NONE) {
            throw new Exception\RemoteException('JSON decode error', $err_data);
        }

        // return json or the raw stringified response body
        return array($code, $json, $err_data);
    }

}
