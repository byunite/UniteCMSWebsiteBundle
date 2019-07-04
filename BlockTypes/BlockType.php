<?php


namespace Unite\CMSWebsiteBundle\BlockTypes;

use Unite\CMSWebsiteBundle\Model\PageContentBlock;
use Unite\CMSWebsiteBundle\Services\BlockTypeManager;
use RuntimeException;

abstract class BlockType implements BlockTypeInterface
{
    const TYPE = null;
    const SITE = null;
    const QUERY_FIELDS = [];

    public static function TYPE() : string {
        if(empty(static::TYPE)) {
            throw new RuntimeException(sprintf('const "TYPE" on BlockType %s missing.', static::class));
        }

        return static::TYPE;
    }

    public static function SITE() : ?string {
        return static::SITE;
    }

    public static function QUERY_FIELDS() : array {
        return static::QUERY_FIELDS;
    }

    /**
     * {@inheritDoc}
     */
    public static function KEY() : string
    {
        return join('_', array_filter([static::TYPE(), static::SITE()]));
    }

    /**
     * {@inheritDoc}
     */
    public function getGraphQLFragment(): ?string
    {
        if(empty(static::QUERY_FIELDS())) {
            return null;
        }

        return sprintf(
            'fragment %s on PageContentBlocksBlock%sVariant { %s }',
            static::TYPE() . BlockTypeManager::FRAGMENT_KEY,
            ucfirst(static::TYPE()),
            join(', ', static::QUERY_FIELDS())
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplateName(): string
    {
        return sprintf('%s.html.twig', static::TYPE());
    }

    /**
     * {@inheritDoc}
     */
    public function execute(PageContentBlock $block) : array
    {
        return ['block' => $block];
    }
}
