<?php
namespace Pmp\Sdk;

class AuthClient
{

    /**
     * This is not a constant, because in the future we may want to
     * have the auth endpoint URI be dynamically deduced from the API itself
     * @var string
     */
    public $AUTH_ENDPOINT = '/auth/access_token'; // make sure to include leading slash

    private $authUri;
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $tokenLastRetrievedTS;

    /**
     * @param string $authUri
     *    URI of the authentication API, e.g.: http://auth.pmp.io/
     * @param string $clientId
     *    the client ID to use for authentication requests
     * @param string $clientSecret
     *    the client secret to use for authentication requests
     */
    public function __construct($authUri, $clientId, $clientSecret) {
        $this->authUri = trim($authUri, '/'); // no trailing slash
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        //-- Need to initialize token firs time around, otherwise fresh auth object is useless.
        $this->getToken();
    }

    /**
     * Gets a token for the given client id and secret
     * @param bool $refresh
     *   whether to get a refreshed token from the API
     * @return string
     * @throws Exception
     */
    public function getToken($refresh=false) {
        if (!$refresh && !empty($this->accessToken)) {
            if (!empty($this->tokenLastRetrievedTS)) {
              $this->accessToken->token_expires_in = $this->accessToken->token_expires_in - (time() - $this->tokenLastRetrievedTS);
            }
            $this->tokenLastRetrievedTS = time();
            return $this->accessToken;
        }

        $uri = $this->authUri . $this->AUTH_ENDPOINT;

        // Authorization header requires a hash of client ID and client secret
        $hash = base64_encode($this->clientId . ":" . $this->clientSecret);

        // GET request needs an authorization header with the generated client hash
        $request = new Request();
        $response = $request->header('Authorization', 'Basic ' . $hash)
                            ->header('Content-Type', 'application/x-www-form-urlencoded')
                            ->data('grant_type', 'client_credentials')
                            ->post($uri);

        // Response code must be 200 and data must be found in response in order to continue
        if ($response['code'] != 200 || empty($response['data'])) {
            $err = "Got non-HTTP-200 and/or empty response from the authentication server";
            $exception = new Exception($err);
            $exception->setDetails($response);
            throw $exception;
            return;
        }

        $data = json_decode($response['data']);
        if (empty($data->access_token)) {
            $err = "Got unexpected empty token from the authentication server";
            $exception = new Exception($err);
            $exception->setDetails($response);
            throw $exception;
            return;
        }

        $this->accessToken = $data;
        //-- Record when was expires_in last retrieved so that when we get auth token from cache, we fix expires_in
        $this->tokenLastRetrievedTS = time();
        return $data;
    }

    /**
     * Revokes a token for the given client id and secret
     * @return bool
     * @throws Exception
     */
    public function revokeToken() {
        $uri = $this->authUri . $this->AUTH_ENDPOINT;

        // Authorization header requires a hash of client ID and client secret
        $hash = base64_encode($this->clientId . ":" . $this->clientSecret);

        // GET request needs an authorization header with the generated client hash
        $request = new Request();
        $response = $request->header('Authorization', 'Basic ' . $hash)
                            ->delete($uri);

        // Response code must be 204 in order to be successful
        if ($response['code'] != 204) {
            $err = "Got unexpected response code from the authentication server";
            $exception = new Exception($err);
            $exception->setDetails($response);
            throw $exception;
            return false;
        }
        $this->accessToken = null;
        return true;
    }
}
