<?php


namespace Installer\Bitbucket;

/**
 * Class Api
 * @package Installer\Bitbucket
 */
class Api extends \Bitbucket\API\Api
{
    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return parent::getClient();
    }
}
