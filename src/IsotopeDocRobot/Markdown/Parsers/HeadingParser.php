<?php

namespace IsotopeDocRobot\Markdown\Parsers;


use IsotopeDocRobot\Markdown\ParserInterface;

class HeadingParser implements ParserInterface
{
    /**
     * {@inheritdoc}
     */
    public function parseMarkdown($data)
    {
        return preg_replace_callback(
            '/<h([1-6])>(.*)<\\/h[1-6]>/u',
            function($matches) {
                $level = $matches[1];
                $content = $matches[2];
                $id = 'deeplink-' . standardize($content);

                return sprintf('<h%s id="%s">%s <a href="%s" title="%s" class="sub_permalink">#</a></h%s>',
                    $level,
                    $id,
                    $content,
                    \Environment::get('request') . '#' . $id,
                    $GLOBALS['TL_LANG']['ISOTOPE_DOCROBOT']['deeplinkLabel'],
                    $level
                );
            },
            $data);
    }
}