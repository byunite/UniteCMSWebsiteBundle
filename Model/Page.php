<?php


namespace Unite\CMSWebsiteBundle\Model;


use Doctrine\Common\Collections\ArrayCollection;

class Page
{

    /**
     * @var Site $site
     */
    protected $site;

    /**
     * @var int $position
     */
    protected $position = 0;

    /**
     * @var string $name
     */
    protected $name = '';

    /**
     * @var string $slug
     */
    protected $slug = '';

    /**
     * @var ArrayCollection $data
     */
    protected $data;

    /**
     * @var PageContentBlock[]|ArrayCollection $contentBlocks
     */
    protected $contentBlocks;

    protected $translations;

    public function __construct(string $name, string $slug)
    {
        $this->name = $name;
        $this->slug = trim($slug, '/');
        $this->contentBlocks = new ArrayCollection();
        $this->data = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    /**
     * @param object $response
     * @return Page
     */
    static function fromGraphQLResponse($response) : Page {
        $page = new Page($response->title, $response->slug->text);

        foreach(get_object_vars($response) as $key => $value) {
            if(!in_array($key, ['title', 'slug', 'blocks', 'translations', 'locale'])) {
                $page->set($key, $value);
            }

            // Allow access translations directly.
            if($key == 'translations') {
                foreach($value as $translation) {
                    if(!empty($translation->locale) && !empty($translation->title) && !empty($translation->slug)) {
                        $page->addTranslation($translation->locale, Page::fromGraphQLResponse($translation));
                    }
                }
            }
        }

        if(!empty($response->blocks)) {
            foreach ($response->blocks as $row) {
                $page->addContentBlock(PageContentBlock::fromGraphQLResponse($row->block));
            }
        }

        return $page;
    }

    public function __toString() : string
    {
        return $this->name;
    }

    /**
     * @return Site
     */
    public function getSite() : Site
    {
        return $this->site;
    }

    /**
     * @param Site $site
     * @return Page
     */
    public function setSite(Site $site) : self
    {
        $this->site = $site;
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
     * @return Page
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return Page
     */
    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     *
     * @return Page
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return PageContentBlock[]|\Doctrine\Common\Collections\ArrayCollection
     */
    public function getContentBlocks()
    {
        return $this->contentBlocks;
    }

    /**
     * @param iterable $contentBlocks
     *
     * @return Page
     */
    public function setContentBlocks(iterable $contentBlocks): self
    {
        $this->contentBlocks->clear();
        foreach($contentBlocks as $contentBlock) {
            $this->addContentBlock($contentBlock);
        }
        return $this;
    }

    /**
     * @param PageContentBlock $contentBlock
     *
     * @return Page
     */
    public function addContentBlock(PageContentBlock $contentBlock) : self
    {
        $this->contentBlocks->add($contentBlock);
        $contentBlock
            ->setPosition($this->contentBlocks->count() - 1)
            ->setPage($this);

        return $this;
    }

    /**
     * @return ArrayCollection|Page[]
     */
    public function getTranslations() : ArrayCollection {
        return $this->translations;
    }

    /**
     * @param string $locale
     * @return Page|null
     */
    public function getTranslation(string $locale) : ?Page {
        return $this->translations->get($locale);
    }

    /**
     * @param string $locale
     * @param Page $page
     * @return Page
     */
    public function addTranslation(string $locale, Page $page): self {
        $this->translations->set($locale, $page);
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
     * @return Page
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
