<?php


namespace Unite\CMSWebsiteBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Site
{
    const CACHE_KEY_PREFIX = 'app.site.cache';
    const CUSTOM_SITE_TEMPLATE_PATH = 'custom/';
    const GENERIC_SITE_TEMPLATE = 'generic';

    /**
     * @var string $secretApiKey
     */
    protected $secretApiKey;

    /**
     * @var string $publicApiKey
     */
    protected $publicApiKey;

    /**
     * @var string|null $currentSlug
     */
    protected $currentSlug = null;

    /**
     * @var null|string $currentLocale
     */
    protected $currentLocale = null;

    /**
     * @var ArrayCollection|Page[] $pages
     */
    protected $pages;

    /**
     * @var string $identifier
     */
    protected $identifier;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $domain
     */
    protected $domain;

    /**
     * @var ArrayCollection $data
     */
    protected $data;

    public function __construct(string $identifier, string $domain, string $secretApiKey, string $publicApiKey, string $currentLocale = null)
    {
        $this->secretApiKey = $secretApiKey;
        $this->publicApiKey = $publicApiKey;
        $this->pages = new ArrayCollection();
        $this->data = new ArrayCollection();
        $this->identifier = $identifier;
        $this->domain = $domain;
        $this->currentLocale = $currentLocale;
    }

    public function __toString() : string
    {
        return $this->name;
    }

    public function getCacheKey() : string
    {
        return self::CACHE_KEY_PREFIX . '.' . $this->getIdentifier() . ($this->currentLocale ? '.' . $this->currentLocale : null);
    }

    public function setCurrentSlug(string $currentSlug) : self {

        if(!$this->pages->containsKey($currentSlug)) {
            throw new NotFoundHttpException(sprintf('Page with slug "%s" not found.', $currentSlug));
        }

        $this->currentSlug = $currentSlug;
        return $this;
    }

    public function getCurrentPage() : ?Page {
        return ($this->currentSlug !== null) ? $this->pages->get($this->currentSlug) : null;
    }

    /**
     * @return string|null
     */
    public function getCurrentLocale(): ?string
    {
        return $this->currentLocale;
    }

    /**
     * @param string|null $currentLocale
     * @return Site
     */
    public function setCurrentLocale(?string $currentLocale): self
    {
        $this->currentLocale = $currentLocale;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecretApiKey(): string
    {
        return $this->secretApiKey;
    }

    /**
     * @param string $secretApiKey
     * @return Site
     */
    public function setSecretApiKey(string $secretApiKey): self
    {
        $this->secretApiKey = $secretApiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getPublicApiKey(): string
    {
        return $this->publicApiKey;
    }

    /**
     * @param string $publicApiKey
     * @return Site
     */
    public function setPublicApiKey(string $publicApiKey): self
    {
        $this->publicApiKey = $publicApiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return Site
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     * @return Site
     */
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Site
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomTemplate(): ?string
    {
        return self::CUSTOM_SITE_TEMPLATE_PATH . $this->getIdentifier();
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return self::GENERIC_SITE_TEMPLATE;
    }

    /**
     * @param string|null $template
     *
     * @return Site
     */
    public function setTemplate(?string $template): self
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return Page[]|\Doctrine\Common\Collections\ArrayCollection
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param iterable $pages
     *
     * @return Site
     */
    public function setPages(iterable $pages): self
    {
        $this->pages->clear();
        foreach($pages as $page) {
            $this->addPage($page);
        }
        return $this;
    }

    public function addPage(Page $page) : self {
        $this->pages->set($page->getSlug(), $page);
        $page->setPosition($this->pages->count() - 1)->setSite($this);
        return $this;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function get(string $key, $default = null) {
        return $this->data->get($key) ?? $default;
    }

    /**
     * @param string $key
     * @param $value
     *
     * @return \Unite\CMSWebsiteBundle\Model\Site
     */
    public function set(string $key, $value) : self {
        $this->data->set($key, $value);
        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key) : bool {
        return $this->has($key);
    }
}
