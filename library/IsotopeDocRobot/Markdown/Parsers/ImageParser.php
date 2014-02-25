<?php

namespace IsotopeDocRobot\Markdown\Parsers;


use IsotopeDocRobot\Markdown\AfterParserInterface;

class ImageParser implements AfterParserInterface
{
    private $language = null;
    private $book = null;
    private $pageModel = null;
    private $version = null;

    public function __construct($language, $book, $pageModel, $version)
    {
        $this->language = $language;
        $this->book = $book;
        $this->pageModel = $pageModel;
        $this->version = $version;
    }

    /**
     * {@inheritdoc}
     */
    public function parseAfter($data)
    {
        return preg_replace_callback(
            '#<docrobot_image path="(.*)" alt="(.*)">#U',
            $this->getMarkupForImageClosure(),
            $data);
    }

    private function getMarkupForImageClosure()
    {
        $language = $this->language;
        $book = $this->book;
        $pageModel = $this->pageModel;
        $version = $this->version;

        return function($matches) use ($language, $book, $pageModel, $version) {

            $imagePath = 'system/cache/isotope/docrobot-mirror/' . $version . '/' . $language . '/' . $book . '/' . $matches[1];
            $imageSize = @getimagesize($imagePath);

            $image      = \Image::get($imagePath, $imageSize[0], $imageSize[1], 'box', null, true);

            // No image found
            if (!$image) {
                return '###Image not found, please adjust documentation on GitHub!###';
            }

            // No resize necessary
            if ($imageSize[0] <= 680) {
                return sprintf('<img src="%s" alt="%s" %s>',
                    $image,
                    $matches[2],
                    $imageSize[3]
                );
            }

            // Generate thumbnail
            $thumb      = \Image::get($imagePath, 680, $imageSize[1], 'box', null, true);
            $thumbSize  = @getimagesize($thumb);

            return sprintf('<figure class="image_container"><a href="%s" data-lightbox="%s" title="%s"><span class="overlay zoom"></span><img src="%s" alt="%s" %s></a></figure>',
                $image,
                uniqid(),
                $matches[2],
                $thumb,
                $matches[2],
                $thumbSize[3]
            );
        };
    }
}