<?php

namespace App\HttpClient;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GraylogClientFactory
{

    private $host;

    private $username;

    private $userpassword;

    public function __construct(string $host, string $username, string $userpassword)
    {
        $this->host = $host;
        $this->username = $username;
        $this->userpassword = $userpassword;
    }

    public function create(): HttpClientInterface
    {
        return HttpClient::createForBaseUri(
            $this->host,
            [
                'auth_basic' => [$this->username, $this->userpassword]
            ]
        );
    }

    public function getHost(): string
    {
        return $this->host;
    }
}
