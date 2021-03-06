<?php

namespace IsotopeDocRobot\Routing;


use IsotopeDocRobot\Context\Context;

class Route
{
    private $name = '';
    private $config = null;
    private $path = '';
    private $trail = array();
    private $children = array();
    private $siblings = array();

    public function __construct($name, $config, $path, $trail)
    {
        $this->name = $name;
        $this->config = $config;
        $this->path = $path;
        $this->trail = $trail;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTrail()
    {
        return $this->trail;
    }

    public function getTitle()
    {
        return $this->getConfig()->title;
    }

    public function setTitle($title)
    {
        $this->getConfig()->title = $title;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getAlias()
    {
        return $this->getConfig()->alias ?: $this->getName();
    }

    public function isIncomplete()
    {
        return $this->getConfig()->incomplete === true;
    }

    public function isNew()
    {
        if ($this->getConfig()->new) {
            $objDateNew = $this->getNewAsDateTime();
            $objDateNow = new \DateTime();

            $objDiff = $objDateNow->diff($objDateNew);
            if ($objDiff->days <= 14) {
                return true;
            }
        }

        return false;
    }

    public function getNewAsDateTime()
    {
        return new \DateTime($this->getConfig()->new);
    }

    public function addChild(Route $child)
    {
        $this->children[$child->getName()] = $child;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function hasChildren()
    {
        return !empty($this->children);
    }

    // oooh, sweet child o' mine
    public function isChildOfMine(Route $route)
    {
        return (boolean) $this->children[$route->getName()];
    }

    public function addSibling(Route $sibling)
    {
        $this->siblings[$sibling->getName()] = $sibling;
    }

    public function getSiblings()
    {
        return $this->siblings;
    }

    public function hasSiblings()
    {
        return !empty($this->siblings);
    }

    public function isSiblingOfMine(Route $route)
    {
        return (boolean) $this->siblings[$route->getName()];
    }

    public function getContent(Context $context)
    {
        // only regular routes have content
        if ($this->getConfig()->type != 'regular') {
            return '';
        }

        // for now only supporting the "index.md" file
        $path = $this->getPath();
        $path .= (($path !== '') ? '/' : '') . 'index.md';

        $path = sprintf('%s/system/cache/isotope/docrobot-mirror/%s/%s/%s/%s',
            TL_ROOT,
            $context->getVersion(),
            $context->getLanguage(),
            $context->getBook(),
            $path);

        if (!is_file($path)) {
            return '';
        }

        return file_get_contents($path);
    }
} 