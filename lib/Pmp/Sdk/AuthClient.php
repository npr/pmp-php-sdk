<?php
namespace Pmp\Sdk;

require_once(dirname(__FILE__) . '/../../restagent/restagent.lib.php');
use restagent\Request as Request;

class AuthClient
{

    /**
     * This is not a constant, because in the future we may want to
     * have the auth endpoint URI be dynamically deduced from the API itself
     * @var string
     */
    public $AUTH_ENDPOINT = 'auth/access_token';

    private $authUri;

    /**
     * @param string $authUri
     *    URI of the authentication API, e.g.: http://auth.pmp.io/
     */
    public function __construct($authUri) {
        if (substr($authUri, -1) != '/') { // normalize
            $authUri = $authUri . '/';
        }
        $this->authUri = $authUri;
    }

    /**
     * Gets a token for the given client id and secret
     * @param string $clientId
     *    the client ID to use for the request
     * @param string $clientSecret
     *    the client secret to use for the request
     * @return string
     * @throws \Exception
     */
    public function getToken($clientId, $clientSecret) {
        $uri = $this->authUri . $this->AUTH_ENDPOINT;

        // Authorization header requires a hash of client ID and client secret
        $hash = base64_encode($clientId . ":" . $clientSecret);

        // GET request needs an authorization header with the generated client hash
        $request = new Request();
        $response = $request->header('Authorization', 'CLIENT_CREDENTIALS ' . $hash)
                            ->get($uri);

        // Response code must be 200 and data must be found in response in order to continue
        if ($response['code'] != 200 || empty($response['data'])) {
            $err = "Got non-HTTP-200 and/or empty response from the authentication server: \n " . print_r($response, true);
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
        $uri = $this->authUri . $this->AUTH_ENDPOINT;

        // Authorization header requires a hash of client ID and client secret
        $hash = base64_encode($clientId . ":" . $clientSecret);

        // GET request needs an authorization header with the generated client hash
        $request = new Request();
        $response = $request->header('Authorization', 'CLIENT_CREDENTIALS ' . $hash)
            ->delete($uri);

        // Response code must be 204 in order to be successful
        if ($response['code'] != 204) {
            $err = "Got unexpected response code from the authentication server: \n " . print_r($response, true);
            throw new \Exception($err);
            return false;
        }
        return true;
    }
}