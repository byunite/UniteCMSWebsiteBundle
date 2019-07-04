<?php

namespace Unite\CMSWebsiteBundle\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Unite\CMSWebsiteBundle\Model\Site;

class UniteCmsClient
{
    protected $uniteCmsBaseUrl;

    /**
     * @var HttpClientInterface
     */
    protected $client;

    public function __construct(string $uniteCmsBaseUrl, HttpClientInterface $client)
    {
        $this->uniteCmsBaseUrl = $uniteCmsBaseUrl;
        $this->client = $client;
    }

    /**
     * @param string $organization
     * @param string $domain
     * @param string $authorization
     * @param string $query
     * @param array $variables
     *
     * @return object
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function request(string $organization, string $domain, string $authorization, string $query, array $variables = []) {
        return json_decode($this->client->request(
            'POST',
            join('/', [$this->uniteCmsBaseUrl, $organization, $domain, 'api']),
            [
                'json' => [
                    'query' => $query,
                    'variables' => $variables,
                ],
                'headers' => [
                    'Authorization' => $authorization,
                ],
            ]
        )->getContent());
    }

    /**
     * @param \Unite\CMSWebsiteBundle\Model\Site $site
     * @param string $domain
     * @param string $query
     * @param array $variables
     *
     * @return object
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function siteRequest(Site $site, string $domain, string $query, array $variables = []) {
        return $this->request($site->getIdentifier(), $domain, $site->getSecretApiKey(), $query, $variables);
    }
}
