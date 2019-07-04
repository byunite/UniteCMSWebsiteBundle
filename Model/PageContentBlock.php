<?php


namespace Unite\CMSWebsiteBundle\Model;


class PageContentBlock
{
    /**
     * @var Page $page
     */
    protected $page;

    /**
     * @var int $position
     */
    protected $position = 0;

    /**
     * @var string $type
     */
    protected $type = '';

    /**
     * @var array $data
     */
    protected $data = [];

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param object $response
     * @return \App\Model\PageContentBlock
     */
    static function fromGraphQLResponse($response) : PageContentBlock
    {
        $block = new PageContentBlock($response->type ?? '');
        $block->setData((array)$response);
        return $block;
    }

    public function __toString() : string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return \App\Model\PageContentBlock
     */
    public function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return \App\Model\PageContentBlock
     */
    public function setData(array $data): self
    {
        unset($data['type']);
        $this->data = $data;
        return $this;
    }

    /**
     * @return \App\Model\Page
     */
    public function getPage(): Page
    {
        return $this->page;
    }

    /**
     * @param \App\Model\Page $page
     *
     * @return \App\Model\PageContentBlock
     */
    public function setPage(Page $page): self
    {
        $this->page = $page;
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
     * @return \App\Model\PageContentBlock
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }
}
