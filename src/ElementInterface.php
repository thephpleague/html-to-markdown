<?php

namespace League\HTMLToMarkdown;

interface ElementInterface
{
    /**
     * @return bool
     */
    public function isBlock();

    /**
     * @return bool
     */
    public function isText();

    /**
     * @return bool
     */
    public function isWhitespace();

    /**
     * @return string
     */
    public function getTagName();

    /**
     * @return string
     */
    public function getValue();

    /**
     * @return ElementInterface|null
     */
    public function getParent();

    /**
     * @param string|string[] $tagNames
     *
     * @return bool
     */
    public function isDescendantOf($tagNames);

    /**
     * @return bool
     */
    public function hasChildren();

    /**
     * @return ElementInterface[]
     */
    public function getChildren();

    /**
     * @return ElementInterface|null
     */
    public function getNext();

    /**
     * @return int
     */
    public function getSiblingPosition();

    /**
     * @return string
     */
    public function getChildrenAsString();

    /**
     * @param string $markdown
     */
    public function setFinalMarkdown($markdown);

    /**
     * @param string $name
     *
     * @return string
     */
    public function getAttribute($name);
}
