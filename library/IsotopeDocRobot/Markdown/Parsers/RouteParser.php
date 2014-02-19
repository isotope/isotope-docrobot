<?php

namespace IsotopeDocRobot\Markdown\Parsers;


use IsotopeDocRobot\Markdown\AfterParserInterface;

class RouteParser implements AfterParserInterface
{
    private $routing = null;
    private $pageModel = null;
    private $version = null;

    public function __construct($routing, $pageModel, $version)
    {
        $this->routing = $routing;
        $this->pageModel = $pageModel;
        $this->version = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function parseAfter($data)
    {
        $routing = $this->routing;
        $pageModel = $this->pageModel;
        $version = $this->version;

        return preg_replace_callback(
            '#<docrobot_route name="(.*)">(.*)</docrobot_route>#U',
            function($matches) use ($routing, $pageModel, $version) {

                $route = $routing->getRoute($matches[1]);

                if ($route === null) {
                    return 'Route "' . $matches[1] . '" does not exist. Please fix the documentation on GitHub!';
                }

                return sprintf('<a href="%s">%s</a>',
                    $routing->getHrefForRoute(
                        $route,
                        $pageModel,
                        $version
                    ),
                    $matches[2]
                );
            },
            $data);
    }
}