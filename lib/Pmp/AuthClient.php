<?php
namespace Pmp;

require_once(dirname(__FILE__).'/../restagent/restagent.lib.php');
use restagent\Request as Request;

class AuthClient
{
    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * Gets a token for the given client id and secret
     * @param string $clientId
     * @param string $clientSecret
     * @return string
     */
    public function getToken($clientId, $clientSecret) {
        $url = $this->url;
        $hash = base64_encode($clientId . ":" . $clientSecret);
        $request = new Request();
        $response = $request->header('Authorization', 'CLIENT_CREDENTIALS ' . $hash)
                            ->get($url);
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
     * @param string $clientSecret
     * @return bool
     */
    public function revokeToken($clientId, $clientSecret) {
        $url = $this->url;
        $hash = base64_encode($clientId . ":" . $clientSecret);
        $request = new Request();
        $response = $request->header('Authorization', 'CLIENT_CREDENTIALS ' . $hash)
            ->delete($url);
        if ($response['code'] != 204) {
            return false;
        }
        return true;
    }
}