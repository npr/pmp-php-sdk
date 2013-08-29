<?php
namespace Pmp;

require_once(dirname(__FILE__).'/../restagent/restagent.lib.php');
use restagent\Request as Request;

class AuthClient
{
    /**
     * @param string $url
     *    authentication endpoint
     */
    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * Gets a token for the given client id and secret
     * @param string $clientId
     *    the client ID to use for the request
     * @param string $clientSecret
     *    the client secret to use for the request
     * @return string
     */
    public function getToken($clientId, $clientSecret) {
        $url = $this->url;

        // Authorization header requires a hash of client ID and client secret
        $hash = base64_encode($clientId . ":" . $clientSecret);

        // GET request needs an authorization header with the generated client hash
        $request = new Request();
        $response = $request->header('Authorization', 'CLIENT_CREDENTIALS ' . $hash)
                            ->get($url);

        // Response code must be 200 and data must be found in response in order to continue
        if ($response['code'] != 200 || empty($response['data'])) {
            return '';
        }
        $data = json_decode($response['data']);
        if (empty($data->access_token)) {
            return '';
        }
        return $data->access_token;
    }

    /**
     * Revokes a token for the given client id and secret
     * @param string $clientId
     *    the client ID to use for the request
     * @param string $clientSecret
     *    the client secret to use for the request
     * @return bool
     */
    public function revokeToken($clientId, $clientSecret) {
        $url = $this->url;

        // Authorization header requires a hash of client ID and client secret
        $hash = base64_encode($clientId . ":" . $clientSecret);

        // GET request needs an authorization header with the generated client hash
        $request = new Request();
        $response = $request->header('Authorization', 'CLIENT_CREDENTIALS ' . $hash)
            ->delete($url);

        // Response code must be 204 in order to be successful
        if ($response['code'] != 204) {
            return false;
        }
        return true;
    }
}