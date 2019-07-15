<?php


namespace Unite\CMSWebsiteBundle\Services;


use Unite\CMSWebsiteBundle\Model\Page;
use Unite\CMSWebsiteBundle\Model\Site;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class SiteManager
{

    /**
     * @var BlockTypeManager $blockTypeManager
     */
    protected $blockTypeManager;

    /**
     * @var TagAwareCacheInterface $cache
     */
    protected $cache;

    /**
     * @var array $sitesMapping
     */
    protected $sitesMapping;

    /**
     * @var string $baseUrl
     */
    protected $baseUrl;

    /**
     * @var UniteCmsClient $client
     */
    protected $client;

    /**
     * @var bool
     */
    protected $appDebug;

    /**
     * @var ArrayCollection
     */
    protected $queryParts;

    /**
     * @var string $defaultDomainIdentifier
     */
    protected $defaultDomainIdentifier;

    public function __construct(BlockTypeManager $blockTypeManager, UniteCmsClient $client, TagAwareCacheInterface $cache, string $baseUrl, string $defaultDomainIdentifier, array $queryParts = [], bool $appDebug = false, array $sitesMapping = [])
    {
        $this->blockTypeManager = $blockTypeManager;
        $this->client = $client;
        $this->cache = $cache;
        $this->baseUrl = $baseUrl;
        $this->sitesMapping = $sitesMapping;
        $this->appDebug = $appDebug;
        $this->defaultDomainIdentifier = $defaultDomainIdentifier;
        $this->queryParts = New ArrayCollection($queryParts);
    }

    public function setQueryPart(string $key, $value) : SiteManager {
        $this->queryParts->set($key, $value);
        return $this;
    }

    public function getQueryParts() : ArrayCollection {
        return $this->queryParts;
    }

    /**
     * Finds a site by a given hostname. Returns null, if not site was found.
     *
     * @param string $host
     *
     * @param string|null $locale
     *
     * @return Site|null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findSiteByHost(string $host, string $locale = null) : ?Site {

        foreach($this->sitesMapping as $identifier => $siteMap) {
            if($this->matchHost($host, $identifier, $siteMap['hosts'] ?? [])) {
                $domain = $siteMap['domain'] ?? $this->defaultDomainIdentifier;
                return $this->loadSiteDetails(
                    new Site($identifier, $domain, $siteMap['secret_api_key'], $siteMap['public_api_key'], $locale),
                    $this->appDebug
                );
            }
        }

        return null;
    }

    /**
     * Loads details for a site from cache or from unite cms.
     *
     * @param Site $site
     * @param bool $forceRefresh
     *
     * @return Site
     * @throws InvalidArgumentException
     */
    public function loadSiteDetails(Site $site, bool $forceRefresh = false) : Site {
        return $this->cache->get($site->getCacheKey(), function(ItemInterface $item) use ($site) {
            $item->tag([
                Site::CACHE_KEY_PREFIX,
                $site->getCacheKey(),
            ]);

            try {
                // Find all available block types.
                $blockTypes = array_filter(array_map(function($type){
                    $extract = [];
                    preg_match('/PageContentBlocksBlock([A-Z][_a-z]+)Variant/', $type->name, $extract);
                    return count($extract) == 2 ? strtolower($extract[1]) : null;
                }, $this->client->siteRequest($site, $site->getDomain(), 'query {
                  __type(name: "VariantsFieldInterface") {
                    possibleTypes {
                          name
                        }
                  }
                }')->data->__type->possibleTypes));

                $filter = $site->getCurrentLocale() ? [
                    'field' => 'locale',
                    'value' => $site->getCurrentLocale(),
                    'operator' => '='
                ] : null;

                if(!empty($this->queryParts->get('filter'))) {
                    $filter = empty($filter) ? $this->queryParts->get('filter') : [
                        'AND' => [
                            $filter,
                            $this->queryParts->get('filter'),
                        ]
                    ];
                }

                // Get all pages with all content, using only block types for this site that are available.
                $query = sprintf('query($sort: [SortInput], $filter: FilterInput) {
                    
                    SiteSetting {
                        %s
                    }
                    
                    %s(sort: $sort, filter: $filter) {
                        result {
                          %s
                          %s {
                            %s {
                              type
                              %s
                            }
                          }
                        }
                        }
                    } %s',
                    $this->queryParts->get('site_setting_fields'),
                    $this->queryParts->get('find_pages_query'),
                    $this->queryParts->get('page_fields'),
                    $this->queryParts->get('blocks_name'),
                    $this->queryParts->get('block_name'),
                    $this->blockTypeManager->getContentBlockFragmentsUsage($site, $blockTypes),
                    $this->blockTypeManager->getContentBlockFragments($site, $blockTypes)
                );
                $variables = [
                    'sort' => $this->queryParts->get('find_pages_sort'),
                    'filter' => $filter,
                ];

                $response = $this->client->siteRequest($site, $site->getDomain(), $query, $variables);

                foreach(get_object_vars($response->data->SiteSetting) as $key => $value) {
                    if($key === 'title') {
                        $site->setName($value);
                    } else {
                        $site->set($key, $value);
                    }
                }

                foreach($response->data->findPage->result as $page) {
                    $site->addPage(Page::fromGraphQLResponse($page));
                }
            } catch (\Exception $e) {
                throw new \ErrorException(sprintf("Error while calling the graphql endpoint: '%s'. \n\nGraphQL Response: %s \n\nGraphQL Query: %s\n\nGraphQL Variables: %s",
                    $e->getMessage(),
                    json_encode($response, JSON_PRETTY_PRINT),
                    $query,
                    json_encode($query, JSON_PRETTY_PRINT)
                ));
            }

            return $site;

        }, ($forceRefresh ? INF : 1.0));
    }

    /**
     * Match a host to a list of (enriched) hosts.
     *
     * @param string $matchHost
     * @param string $key
     * @param array $hosts
     *
     * @return bool
     */
    protected function matchHost(string $matchHost, string $key, array $hosts = []) : bool {
        $hosts = !empty($hosts) ? $hosts : [$key . '.' . $this->baseUrl];
        foreach($hosts as $host) {
            if(substr($host, 0, 4) !== 'www.') {
                $prefixedHost = 'www.' . $host;
                if(!in_array($prefixedHost, $hosts)) {
                    $hosts[] = $prefixedHost;
                }
            }
        }
        return in_array($matchHost, $hosts);
    }
}
