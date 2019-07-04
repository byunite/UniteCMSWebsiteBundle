<?php


namespace Unite\CMSWebsiteBundle\Services;


use Unite\CMSWebsiteBundle\BlockTypes\BlockTypeInterface;
use Unite\CMSWebsiteBundle\Model\PageContentBlock;
use Unite\CMSWebsiteBundle\Model\Site;
use Doctrine\Common\Collections\ArrayCollection;

class BlockTypeManager
{
    const FRAGMENT_KEY = 'BlockTypeFragment';

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|BlockTypeInterface[]
     */
    protected $blockTypes;

    /**
     * @var bool
     */
    protected $appDebug;

    public function __construct(bool $appDebug = false)
    {
        $this->appDebug = $appDebug;
        $this->blockTypes = new ArrayCollection();
    }

    public function registerBlockType(BlockTypeInterface $blockType) {
        $this->blockTypes->set($blockType::KEY(), $blockType);
    }

    /**
     * @param $type
     * @param \Unite\CMSWebsiteBundle\Model\Site|null $site
     *
     * @return BlockTypeInterface|null
     */
    public function getType($type, Site $site = null) : ?BlockTypeInterface {
        if($site && $this->blockTypes->containsKey($type . '_' . $site->getIdentifier())) {
            return $this->blockTypes->get($type . '_' . $site->getIdentifier());
        }
        $blockType = $this->blockTypes->get($type);

        if($this->appDebug && empty($blockType) && function_exists('dump')) {
            dump('MISSING TYPE: ' . $type);
            dump($site);
            dump($this);
        }

        return $blockType;
    }

    /**
     * @param \Unite\CMSWebsiteBundle\Model\Site $site
     * @param array $types
     *
     * @return string
     */
    public function getContentBlockFragments(Site $site, array $types) : string {
        return join("\n", array_map(function($type) use ($site) {
            $type = $this->getType($type, $site);
            return $type ? $type->getGraphQLFragment() : null;
        }, array_filter($types, function($type) use ($site) {
            return !empty($this->getType($type, $site));
        })));
    }

    /**
     * @param \Unite\CMSWebsiteBundle\Model\Site $site
     * @param array $types
     *
     * @return string
     */
    public function getContentBlockFragmentsUsage(Site $site, array $types) : string {
        return join(', ', array_map(function($type){
            return '...' . $type . self::FRAGMENT_KEY;
        }, array_filter($types, function($type) use ($site) {
            $blockType = $this->getType($type, $site);
            return $blockType && $blockType->getGraphQLFragment();
        })));
    }

    /**
     * @param \Unite\CMSWebsiteBundle\Model\Site $site
     *
     * @return ArrayCollection
     */
    public function renderBlocks(Site $site) : ArrayCollection {
        if(!$site->getCurrentPage()) {
            return [];
        }
        return $site->getCurrentPage()->getContentBlocks()->map(function(PageContentBlock $block) use ($site) {
            $type = $this->getType($block->getType(), $site);
            return $type ? [
                'template' => $type->getTemplateName(),
                'parameter' => $type->execute($block),
            ] : null;
        })->filter(function($block) {
            return !empty($block);
        });
    }
}
