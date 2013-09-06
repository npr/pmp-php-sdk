<?php
namespace Pmp;

require_once(dirname(__FILE__).'/../restagent/restagent.lib.php');
use restagent\Request as Request;

class AuthClient
{

    // This is not a constant, because in the future we may want to
    // have auth endpoint URL be dynamically deduced from the API itself.
    public $AUTH_ENDPOINT = 'auth/access_token';

    private $host;

    /**
     * @param string $host
     *    URL of the authentication host, e.g.: http://auth.pmp.io/
     */
    public function __construct($host) {
        if (substr($host, -1) != '/') { // normalize
            $host = $host . '/';
        }
        $this->host = $host;
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
        $url = $this->host . $this->AUTH_ENDPOINT;

        // Authorization header requires a hash of client ID and client secret
        $hash = base64_encode($clientId . ":" . $clientSecret);

        // GET request needs an authorization header with the generated client hash
        $request = new Request();
        $response = $request->header('Authorization', 'CLIENT_CREDENTIALS ' . $hash)
                            ->get($url);

        // Response code must be 200 and data must be found in response in order to continue
        if ($response['code'] != 200 || empty($response['data'])) {
            $err = "Got unexpected response from the authentication server: \n " . print_r($response, true);
            throw new \Exception($err);
            return;
        }

        $data = json_decode($response['data']);
        if (empty($data->access_token)) {
            $err = "Got unexpected empty token from the authentication server: \n " . print_r($response, true);
            throw new \Exception($err);
            return;
        }

        return $data;
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
        $url = $this->host . $this->AUTH_ENDPOINT;

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