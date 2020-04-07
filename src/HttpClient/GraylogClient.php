<?php

namespace App\HttpClient;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GraylogClient
{
    private $host;
    private $username;
    private $userpassword;

    private $client;

    public function __construct(string $host, string $username, string $userpassword)
    {
        $this->host = $host;
        $this->username = $username;
        $this->userpassword = $userpassword;
    }

    private function getClient(): HttpClientInterface
    {
        if (null === $this->client) {
            $this->client = HttpClient::createForBaseUri(
                $this->host,
                [
                    'auth_basic' => [$this->username, $this->userpassword],
                    'headers' => ['X-Requested-By' => 'graylog-cli', 'Accept' => 'application/json'],
                ]
            );
        }

        return $this->client;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function fetchStreams(): array
    {
        $response = $this->getClient()->request('GET', '/api/streams');
        $json = $response->getContent();

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function fetchVersion(): array
    {
        $response = $this->getClient()->request('GET', '/api');
        $json = $response->getContent();

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function searchAbsolute(string $streamId, string $query, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $queryArgsArray = [
            'query' => $query,
            'sort' => 'timestamp:asc',
            'filter' => 'streams:' . $streamId
        ];

        if (null !== $dateFrom) {
            $fromTime = new \DateTime($dateFrom);
        } else {
            $fromTime = new \DateTime('now');
            $fromTime->modify('- 2 seconds');
        }

        if (null !== $dateTo) {
            $toTime = new \DateTime($dateTo);
        } else {
            $toTime = new \DateTime('now');
            $toTime->modify('- 1 seconds');
        }

        $fromTime->setTime($fromTime->format('H'), $fromTime->format('i'), $fromTime->format('s'), 0);
        $toTime->setTime($toTime->format('H'), $toTime->format('i'), $toTime->format('s'), 0);

        $clonedFromTime = clone $fromTime;
        $clonedFromTime->setTimezone(new \DateTimeZone("UTC"));

        $cloneToTime = clone $toTime;
        $cloneToTime->setTimezone(new \DateTimeZone("UTC"));

        $queryArgsArray['from'] = $clonedFromTime->format("Y-m-d\TH:i:s.000\Z");
        $queryArgsArray['to'] = $cloneToTime->format("Y-m-d\TH:i:s.000\Z");


        $response = $this->getClient()->request('GET', '/api/search/universal/absolute', ['query' => $queryArgsArray]);
        $json = $response->getContent();

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public function searchRelative(string $streamId, string $query, int $range, array $sort = []): array
    {
        $queryArgsArray = [
            'query' => $query,
            'filter' => 'streams:' . $streamId,
            'range' => $range,
            'decorate' => 'true',
        ];

        if (!empty($sort)) {
            $queryArgsArray['sort'] = implode(',', $sort);
        } else {
            $queryArgsArray['sort'] = 'timestamp:asc';
        }

        $response = $this->getClient()->request('GET', '/api/search/universal/relative', ['query' => $queryArgsArray]);
        $json = $response->getContent();

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
