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

    public function __construct(BlockTypeManager $blockTypeManager, UniteCmsClient $client, TagAwareCacheInterface $cache, string $baseUrl, array $sitesMapping = [], bool $appDebug = false)
    {
        $this->blockTypeManager = $blockTypeManager;
        $this->client = $client;
        $this->cache = $cache;
        $this->baseUrl = $baseUrl;
        $this->sitesMapping = $sitesMapping;
        $this->appDebug = $appDebug;
        $this->queryParts = New ArrayCollection([
            'find' => 'findPage',
            'sort' => [[
                'field' => 'position',
                'order' => 'ASC',
            ]],
            'page_fields' => '
                title,
                slug { text },
                menu_button,
                meta_image {
                    url
                },
                meta_description
            ',
            'blocks_name' => 'blocks',
            'block_name' => 'block',
        ]);
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
     * @return Site|null
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findSiteByHost(string $host) : ?Site {

        foreach($this->sitesMapping as $identifier => $siteMap) {
            if($this->matchHost($host, $identifier, $siteMap['hosts'] ?? [])) {
                return $this->loadSiteDetails(
                    new Site($identifier, $siteMap['secret_api_key'], $siteMap['public_api_key']),
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

            // Find all available block types.
            $blockTypes = array_filter(array_map(function($type){
                $extract = [];
                preg_match('/PageContentBlocksBlock([A-Z][_a-z]+)Variant/', $type->name, $extract);
                return count($extract) == 2 ? strtolower($extract[1]) : null;
            }, $this->client->siteRequest($site, 'campaign', 'query {
              __type(name: "VariantsFieldInterface") {
                possibleTypes {
                      name
                    }
              }
            }')->data->__type->possibleTypes));

            // Get all pages with all content, using only block types for this site that are available.
            $response = $this->client->siteRequest($site, 'campaign', sprintf('query($sort: [SortInput]) {
                
                SiteSetting {
                    title,
                    meta_image {
                        url
                    },
                    meta_description
                }
                
                %s(sort: $sort) {
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
                $this->queryParts->get('find'),
                $this->queryParts->get('page_fields'),
                $this->queryParts->get('blocks_name'),
                $this->queryParts->get('block_name'),
                $this->blockTypeManager->getContentBlockFragmentsUsage($site, $blockTypes),
                $this->blockTypeManager->getContentBlockFragments($site, $blockTypes)
            ), ['sort' => $this->queryParts->get('sort')]);

            $site
                ->setName($response->data->SiteSetting->title ?? '')
                ->setMetaImage($response->data->SiteSetting->meta_image ? $response->data->SiteSetting->meta_image->url : null)
                ->setMetaDescription($response->data->SiteSetting->meta_description);

            foreach($response->data->findPage->result as $page) {
                $site->addPage(Page::fromGraphQLResponse($page));
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
