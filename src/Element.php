<?php

namespace League\HTMLToMarkdown;

class Element implements ElementInterface
{
    /**
     * @var \DOMNode
     */
    protected $node;

    /**
     * @var ElementInterface|null
     */
    private $nextCached;

    public function __construct(\DOMNode $node)
    {
        $this->node = $node;
    }

    /**
     * @return bool
     */
    public function isBlock()
    {
        switch ($this->getTagName()) {
            case 'blockquote':
            case 'body':
            case 'code':
            case 'div':
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
            case 'hr':
            case 'html':
            case 'li':
            case 'p':
            case 'ol':
            case 'ul':
                return true;
            default:
                return false;
        }
    }

    /**
     * @return bool
     */
    public function isText()
    {
        return $this->getTagName() === '#text';
    }

    /**
     * @return bool
     */
    public function isWhitespace()
    {
        return $this->getTagName() === '#text' && trim($this->getValue()) === '';
    }

    /**
     * @return string
     */
    public function getTagName()
    {
        return $this->node->nodeName;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->node->nodeValue;
    }

    /**
     * @return ElementInterface|null
     */
    public function getParent()
    {
        return new static($this->node->parentNode) ?: null;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->node->hasChildNodes();
    }

    /**
     * @return ElementInterface[]
     */
    public function getChildren()
    {
        $ret = array();
        /** @var \DOMNode $node */
        foreach ($this->node->childNodes as $node) {
            $ret[] = new static($node);
        }

        return $ret;
    }

    /**
     * @return ElementInterface|null
     */
    public function getNext()
    {
        if ($this->nextCached === null) {
            $nextNode = $this->getNextNode($this->node);
            if ($nextNode !== null) {
                $this->nextCached = new static($nextNode);
            }
        }

        return $this->nextCached;
    }

    /**
     * @param \DomNode $node
     * @param bool $checkChildren
     *
     * @return \DomNode|null
     */
    private function getNextNode($node, $checkChildren = true)
    {
        if ($checkChildren && $node->firstChild) {
            return $node->firstChild;
        }

        if ($node->nextSibling) {
            return $node->nextSibling;
        }

        if ($node->parentNode) {
            return $this->getNextNode($node->parentNode, false);
        }
    }

    /**
     * @param string[]|string $tagNames
     *
     * @return bool
     */
    public function isDescendantOf($tagNames)
    {
        if (!is_array($tagNames)) {
            $tagNames = array($tagNames);
        }

        for ($p = $this->node->parentNode; $p !== false; $p = $p->parentNode) {
            if (is_null($p)) {
                return false;
            }

            if (in_array($p->nodeName, $tagNames)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $markdown
     */
    public function setFinalMarkdown($markdown)
    {
        $markdown_node = $this->node->ownerDocument->createTextNode($markdown);
        $this->node->parentNode->replaceChild($markdown_node, $this->node);
    }

    /**
     * @return string
     */
    public function getChildrenAsString()
    {
        return $this->node->C14N();
    }

    /**
     * @return int
     */
    public function getSiblingPosition()
    {
        $position = 0;

        // Loop through all nodes and find the given $node
        foreach ($this->getParent()->getChildren() as $current_node) {
            if (!$current_node->isWhitespace()) {
                $position++;
            }

            // TODO: Need a less-buggy way of comparing these
            // Perhaps we can somehow ensure that we always have the exact same object and use === instead?
            if ($this->equals($current_node)) {
                break;
            }
        }

        return $position;
    }

    /**
     * @return int
     */
    public function getListItemLevel()
    {
        $level = 0;
        $parent = $this->getParent();

        while ($parent !== null && $parent->node->parentNode) {
            if ($parent->getTagName() === 'li') {
                $level++;
            }
            $parent = $parent->getParent();
        }

        return $level;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getAttribute($name)
    {
        if ($this->node instanceof \DOMElement) {
            return $this->node->getAttribute($name);
        }

        return '';
    }

    /**
     * @param ElementInterface $element
     *
     * @return bool
     */
    public function equals(ElementInterface $element)
    {
        if ($element instanceof self) {
            return $element->node === $this->node;
        }

        return $element === $this;
    }
}
