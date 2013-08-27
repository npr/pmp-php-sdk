<?php
namespace Pmp;

interface AuthClientInterface extends Iterator
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
     * @return AuthClientInterface
     */
    public function revokeToken($clientId, $clientSecret);
}