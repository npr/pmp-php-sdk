<?php
namespace Pmp\Sdk;

use \Guzzle\Http\Client;

/**
 * PMP common HTTP utils
 *
 * Methods to help abstract out some common guzzle setup/usage
 *
 */
class Http
{
    const CONTENT_TYPE = 'application/vnd.collection.doc+json';
    const USER_AGENT   = 'pmp-php-sdk';
    const TIMEOUT_S    = 5;
    const CURL_COULDNT_RESOLVE_HOST = 6;

    /**
     * Make a normal bearer-auth request
     *
     * @param string $method the http method
     * @param string $url the absolute location
     * @param string $token the auth token
     * @param array $data optional body data
     * @return array($status, $jsonObj) the response status and body
     */
    static public function bearerRequest($method, $url, $token = null, $data = null) {
        list($client, $req) = self::_buildRequest($method, $url);

        // additional headers and data
        $req->setHeader('Accept', self::CONTENT_TYPE);
        $req->setHeader('Content-Type', self::CONTENT_TYPE);
        if ($token) {
            $req->setHeader('Authorization', "Bearer $token");
        }
        if ((strtolower($method) == 'post' || strtolower($method) == 'put') && !empty($data)) {
            $req->setBody(json_encode($data));
        }

        return self::_sendRequest($client, $req);
    }

    /**
     * Make a basic-auth'd request to the auth API
     *
     * @param string $method the http method
     * @param string $url the absolute location
     * @param string $basicAuth the basic auth string
     * @param array $postData optional POST data
     * @return array($status, $jsonObj) the response status and body
     */
    static public function basicRequest($method, $url, $basicAuth, $postData = null) {
        list($client, $req) = self::_buildRequest($method, $url);

        // additional headers and data
        $req->setHeader('Accept', 'application/json');
        $req->setHeader('Authorization', $basicAuth);
        if (strtolower($method) == 'post' && !empty($postData)) {
            $req->setHeader('Content-Type', 'application/x-www-form-urlencoded');
            foreach ($postData as $key => $value) {
                if ($value) {
                    $req->setPostField($key, $value);
                }
            }
        }

        return self::_sendRequest($client, $req);
    }

    /**
     * Build a guzzle request object
     *
     * @param string $method the http method
     * @param string $url the absolute location
     * @return array(Client, Request) the guzzle client and request
     */
    static private function _buildRequest($method, $url) {
        $client = new Client();
        $opts = array('timeout' => self::TIMEOUT_S);
        $req = $client->createRequest($method, $url, $opts);
        $req->setHeader('User-Agent', self::USER_AGENT);
        return array($client, $req);
    }

    /**
     * Send a request and handle the response
     *
     * @param Client $client the client object
     * @param Request $req the request object
     * @return array($status, $jsonObj) the response status and body
     */
    static private function _sendRequest($client, $req) {
        $err_data = array('method' => $req->getMethod(), 'url' => $req->getUrl());

        // make the request, catching guzzle errors
        try {
            $resp = $client->send($req);
        }
        catch (\Guzzle\Http\Exception\BadResponseException $e) {
            $resp = $e->getResponse();
        }
        catch (\Guzzle\Http\Exception\CurlException $e) {
            // ConnectException doesn't exist yet - catch curl one manually
            if ($e->getErrorNo() == self::CURL_COULDNT_RESOLVE_HOST) {
                throw new Exception\HostException('Unable to resolve host', $err_data);
            }
            else {
                throw $e;
            }
        }
        $code = $resp->getStatusCode();
        $body = $resp->getBody();
        $json = json_decode($body);
        $err_data['code'] = $code;
        $err_data['body'] = "$body";

        // debug logger
        if (getenv('DEBUG') == '1') {
            echo "# $code $method $url\n";
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
        else if (is_null($json) && json_last_error() != JSON_ERROR_NONE) {
            throw new Exception\RemoteException('JSON decode error', $err_data);
        }

        // return json or the raw stringified response body
        return array($code, $json);
    }

}
