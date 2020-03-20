<?php


namespace Installer\Bitbucket\Auth;


use Bitbucket\API\Http\Listener\BasicAuthListener;
use Bitbucket\API\Http\Listener\ListenerInterface;

/**
 * Class AuthenticatorFactory
 * @package Installer\Bitbucket\Auth
 */
class AuthenticatorFactory
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * AuthenticatorFactory constructor.
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return ListenerInterface
     */
    public function createListener(): ListenerInterface
    {
        return new BasicAuthListener($this->getUsername(), $this->getPassword());
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
