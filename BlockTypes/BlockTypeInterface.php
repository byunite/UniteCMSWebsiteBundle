<?php

namespace Unite\CMSWebsiteBundle\BlockTypes;

use Unite\CMSWebsiteBundle\Model\PageContentBlock;

interface BlockTypeInterface
{

    /**
     * Return the identifier of this block.
     *
     * This is the block type name with an optional '_' . $site suffix.
     * @return string
     */
    public static function KEY() : string;

    /**
     * Return the graphql query fragment for this block.
     *
     * @return string|null
     */
    public function getGraphQLFragment(): ?string;

    /**
     * Return the template name for this block.
     *
     * @return string
     */
    public function getTemplateName(): string;

    /**
     * Called before rendering. Return value will be passed to template.
     *
     * @param PageContentBlock $block
     *
     * @return array
     */
    public function execute(PageContentBlock $block) : array;
}
