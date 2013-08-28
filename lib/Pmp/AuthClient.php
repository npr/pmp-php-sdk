<?php
namespace Pmp;

class AuthClient
{
    /**
     * Gets a token for the given client id and secret
     * @param string $clientId
     * @param string $clientSecret
     * @return string
     */
    public function getToken($clientId, $clientSecret) {

    }

    /**
     * Revokes a token for the given client id and secret
     * @param string $clientId
     * @param string $clientSecret
     * @return AuthClient
     */
    public function revokeToken($clientId, $clientSecret) {

    }
}