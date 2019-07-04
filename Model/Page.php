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
     * @var string|null $menu_button
     */
    protected $menu_button = null;

    /**
     * @var string|null $meta_image
     */
    protected $meta_image = null;

    /**
     * @var string|null $meta_description
     */
    protected $meta_description = null;

    /**
     * @var PageContentBlock[]|ArrayCollection $contentBlocks
     */
    protected $contentBlocks;

    public function __construct(string $name, string $slug)
    {
        $this->name = $name;
        $this->slug = trim($slug, '/');
        $this->contentBlocks = new ArrayCollection();
    }

    /**
     * @param object $response
     * @return \App\Model\Page
     */
    static function fromGraphQLResponse($response) : Page {
        $page = new Page($response->title, $response->slug->text);
        $page
            ->setMenuButton($response->menu_button)
            ->setMetaImage($response->meta_image ? $response->meta_image->url : null)
            ->setMetaDescription($response->meta_description);

        foreach($response->blocks as $row) {
            $page->addContentBlock(PageContentBlock::fromGraphQLResponse($row->block));
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
     * @return \App\Model\Page
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
     * @return \App\Model\Page
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
     * @return \App\Model\Page
     */
    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMenuButton(): ?string
    {
        return $this->menu_button;
    }

    /**
     * @param string|null $menu_button
     *
     * @return \App\Model\Page
     */
    public function setMenuButton(?string $menu_button): self
    {
        $this->menu_button = $menu_button;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMetaImage(): ?string
    {
        return $this->meta_image;
    }

    /**
     * @param string|null $meta_image
     *
     * @return \App\Model\Page
     */
    public function setMetaImage(?string $meta_image): self
    {
        $this->meta_image = $meta_image;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMetaDescription(): ?string
    {
        return $this->meta_description;
    }

    /**
     * @param string|null $meta_description
     * @return \App\Model\Page
     */
    public function setMetaDescription(?string $meta_description): self
    {
        $this->meta_description = $meta_description;
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
     * @return \App\Model\Page
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return \App\Model\PageContentBlock[]|\Doctrine\Common\Collections\ArrayCollection
     */
    public function getContentBlocks()
    {
        return $this->contentBlocks;
    }

    /**
     * @param iterable $contentBlocks
     *
     * @return \App\Model\Page
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
     * @param \App\Model\PageContentBlock $contentBlock
     *
     * @return \App\Model\Page
     */
    public function addContentBlock(PageContentBlock $contentBlock) : self
    {
        $this->contentBlocks->add($contentBlock);
        $contentBlock
            ->setPosition($this->contentBlocks->count() - 1)
            ->setPage($this);

        return $this;
    }
}
