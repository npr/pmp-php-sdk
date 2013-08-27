<?php
namespace Pmp;

interface AuthClient extends Iterator
{
    /**
     * Gets a token for the given client id and secret
     * @param $clientId
     * @param $clientSecret
     * @return string
     */
    public function getToken($clientId, $clientSecret);

    /**
     * Revokes a token for the given client id and secret
     * @param $clientId
     * @param $clientSecret
     * @return AuthClient
     */
    public function revokeToken($clientId, $clientSecret);
}