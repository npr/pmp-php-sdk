<?php
namespace Pmp\Sdk;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;

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
        $req = self::_buildRequest($method, $url);

        // additional headers and data
        $req->setHeader('Content-Type', self::CONTENT_TYPE);
        if ($token) {
            $req->setHeader('Authorization', "Bearer $token");
        }
        if (!empty($data)) {
            $req->setBody(json_encode($data));
        }

        return self::_sendRequest($req);
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
        $req = self::_buildRequest($method, $url);

        // additional headers and data
        $req->setHeader('Accept', 'application/json');
        $req->setHeader('Authorization', $basicAuth);
        if (!empty($postData)) {
            $req->setHeader('Content-Type', 'application/x-www-form-urlencoded');
            foreach ($postData as $key => $value) {
                if ($value) {
                    $req->setPostField($key, $value);
                }
            }
        }

        return self::_sendRequest($req);
    }

    /**
     * Build a guzzle request object
     *
     * @param string $method the http method
     * @param string $url the absolute location
     * @return Request the guzzle request
     */
    static private function _buildRequest($method, $url) {
        $client = new Client();
        $opts = array('timeout' => self::TIMEOUT_S);
        $req = $client->createRequest($method, $url, $opts);
        $req->setHeader('User-Agent', self::USER_AGENT);
        return $req;
    }

    /**
     * Send a request and handle the response
     *
     * @param Request $req the request object
     * @return array($status, $jsonObj) the response status and body
     */
    static private function _sendRequest($req) {
        try {
            $client = new Client();
            $resp = $client->send($req);
        }
        catch (BadResponseException $e) {
            $resp = $e->getResponse();
        }
        $code = $resp->getStatusCode();
        $body = $resp->getBody();
        $json = json_decode($body);

        // debug logger
        if (getenv('DEBUG') == '1') {
            echo "# $code {$req->getMethod()} {$req->getUrl()}\n";
        }

        // handle unexpected response data
        if ($code != 204 && empty($resp)) {
            $e = new Exception("Got unexpected empty document while retrieving $url", $code);
            throw $e;
        }
        else if (is_null($json) && json_last_error() != JSON_ERROR_NONE) {
            $e = new Exception("JSON decode error for document $url");
            $e->setDetails($body);
            throw $e;
        }

        // well heck, it actually worked!
        return array($code, $json);
    }

}
