<?php

namespace Pmp\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Pmp\Sdk\Exception\AuthException;
use Pmp\Sdk\Exception\HostException;
use Pmp\Sdk\Exception\NotFoundException;
use Pmp\Sdk\Exception\RemoteException;

/**
 * HTTP helper functions for common Guzzle setup and usage
 */
class Http
{
    /**
     * Content type for requests via bearer authentication
     *
     * @var string
     */
    const CONTENT_TYPE = 'application/vnd.collection.doc+json';

    /**
     * Prefix for the user agent
     *
     * @var string
     */
    const USER_AGENT_PREFIX = 'phpsdk/v';

    /**
     * Timeout for all API requests
     *
     * @var int
     */
    const TIMEOUT_S = 5; // seconds

    /**
     * Global setting for gzip compression support
     *
     * @var bool
     */
    static protected $optGzip = true;

    /**
     * Global setting for minimal responses
     *
     * @var bool
     */
    static protected $optMinimal = true;

    /**
     * Set advanced options for HTTP requests
     *
     * @param array $options the options to set
     */
    static public function setOptions($options = [])
    {
        if (isset($options['gzip'])) {
            self::$optGzip = !empty($options['gzip']);
        }
        if (isset($options['minimal'])) {
            self::$optMinimal = !empty($options['minimal']);
        }
    }

    /**
     * Make a request using bearer token authentication
     *
     * @param string $method the HTTP method
     * @param string $url the absolute location
     * @param string $token the auth token
     * @param array $data body data
     * @return array the response status and body as array(int $status, \StdClass $body, array $rawResponseData)
     */
    static public function bearerRequest($method, $url, $token = null, $data = null)
    {
        $options = [
            'headers' => [
                'User-Agent' => self::USER_AGENT_PREFIX . \Pmp\Sdk::VERSION,
                'Accept' => self::CONTENT_TYPE,
                'Content-Type' => self::CONTENT_TYPE,
            ]
        ];

        if (self::$optGzip) {
            $options['headers']['Accept-Encoding'] = 'gzip,deflate';
        }
        if ($token) {
            $options['headers']['Authorization'] = "Bearer $token";
        }
        if ((strtolower($method) == 'post' || strtolower($method) == 'put') && !empty($data)) {
            $options['body'] = json_encode($data);
        }

        // preferences - only agree to minimal responses for non-home-docs
        $path = parse_url($url, PHP_URL_PATH);
        if (self::$optMinimal && !empty($path)) {
            $options['headers']['Prefer'] = 'return=minimal';
        }

        return self::_sendRequest($method, $url, $options);
    }

    /**
     * Make a request using basic authentication
     *
     * @param string $method the HTTP method
     * @param string $url the absolute location
     * @param string $basicAuth the basic auth string
     * @param array $postData POST data
     * @return array the response status and body as array(int $status, \StdClass $body, array $rawData)
     */
    static public function basicRequest($method, $url, $basicAuth, $postData = null)
    {
        $options = [
            'headers' => [
                'User-Agent' => self::USER_AGENT_PREFIX . \Pmp\Sdk::VERSION,
                'Accept' => 'application/json',
                'Authorization' => $basicAuth,
            ]
        ];
        if (strtolower($method) == 'post' && !empty($postData)) {
            $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            $formParams = [];
            foreach ($postData as $key => $value) {
                if ($value) {
                    $formParams[$key] = $value;
                }
            }
            $options['form_params'] = $formParams;
        }

        return self::_sendRequest($method, $url, $options);
    }

    /**
     * Send a request and handle the response
     *
     * @param string $method the HTTP method
     * @param string $url the absolute location
     * @param array $options the request options
     * @return array the response status and body as array(int $status, \StdClass $body, array $rawData)
     */
    static private function _sendRequest($method, $url, $options)
    {
        $client = new Client();
        $options['timeout'] = self::TIMEOUT_S;
        $rawData = ['method' => $method, 'url' => $url];

        // make the request, catching Guzzle errors (except most GuzzleException instances)
        try {
            $response = $client->request($method, $url, $options);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        } catch (GuzzleException $e) {
            if (strpos($e->getMessage(), 'cURL error 6: Could not resolve host') !== false) {
                throw new HostException('Unable to resolve host', $rawData);
            }
            throw $e;
        }
        $code = $response->getStatusCode();
        $body = $response->getBody();
        $json = json_decode($body);
        $rawData['code'] = $code;
        $rawData['body'] = "$body";

        // debug logger
        if (getenv('DEBUG') == '1' || getenv('DEBUG') == '2') {
            echo "# $code $method $url\n";
        }
        if (getenv('DEBUG') == '2') {
            echo "  $body\n";
        }

        // handle bad response data
        if ($code != 204 && empty($body)) {
            throw new RemoteException('Empty Document', $rawData);
        } else if ($code == 401) {
            throw new AuthException('Unauthorized', $rawData);
        } else if ($code == 403) {
            throw new NotFoundException('Forbidden', $rawData);
        } else if ($code == 404) {
            throw new NotFoundException('Not Found', $rawData);
        } else if ($code < 200) {
            throw new RemoteException('Informational', $rawData);
        } else if ($code > 299 && $code < 400) {
            throw new RemoteException('Redirection', $rawData);
        } else if ($code > 399 && $code < 500) {
            throw new RemoteException('Client Error', $rawData);
        } else if ($code > 499) {
            throw new RemoteException('Server Error', $rawData);
        } else if ($code != 204 && is_null($json) && json_last_error() != JSON_ERROR_NONE) {
            throw new RemoteException('JSON decode error', $rawData);
        }

        // return status code, JSON decoded body, and raw request/response data
        return [$code, $json, $rawData];
    }
}
