<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 6/14/13
 * Time: 11:48 AM
 * To change this template use File | Settings | File Templates.
 */

namespace IsotopeDocRobot\Maintenance;

use IsotopeDocRobot\Markdown\Parsers\MessageParser;
use IsotopeDocRobot\Markdown\Parsers\NewVersionParser;
use IsotopeDocRobot\Markdown\Parsers\RootParser;
use IsotopeDocRobot\Routing\Routing;
use IsotopeDocRobot\Service\GitHubBookParser;
use IsotopeDocRobot\Service\GitHubConnector;
use IsotopeDocRobot\Service\ParserCollection;

class Update implements \executable
{

    /**
     * Return true if the module is active
     * @return boolean
     */
    public function isActive()
    {
        return false;
    }


    /**
     * Generate the module
     * @return string
     */
    public function run()
    {
        if (\Input::post('FORM_SUBMIT') == 'isotope-docrobot-update') {
            foreach (\Input::post('version') as $version) {
                foreach (\Input::post('lang') as $lang) {
                    foreach (\Input::post('book') as $book) {

                        if (\Input::post('fetch') == 'yes') {
                            $connector = new GitHubConnector($version, $lang, $book);
                            $connector->purgeCache();
                            $connector->updateAll();
                        }

                        try {
                            $routing = new Routing(
                                sprintf('system/cache/isotope/docrobot-mirror/%s/%s/%s/config.json',
                                    $version,
                                    $lang,
                                    $book)
                            );
                        } catch (\InvalidArgumentException $e) {
                            continue;
                        }

                        $parserCollection = new ParserCollection();
                        $parserCollection->addParser(new NewVersionParser());
                        $parserCollection->addParser(new MessageParser());
                        $parserCollection->addParser(new RootParser($version));

                        $parser = new GitHubBookParser($version, $lang, $book, $routing, $parserCollection);
                        $parser->loadLanguage();
                        $parser->updateFromMirror();
                        $parser->resetLanguage();
                    }
                }
            }
        }

        $objTemplate = new \BackendTemplate('be_isotope_docrobot_maintenance');
        $objTemplate->action = ampersand(\Environment::get('request'));
        $objTemplate->headline = specialchars('Isotope DocRobot');

        $arrOptions = array();
        foreach (trimsplit(',', $GLOBALS['TL_CONFIG']['iso_docrobot_versions']) as $strVersion) {
            $arrOptions[] = array(
                'value' => $strVersion,
                'label' => $strVersion
            );
        }

        $arrSettings['id'] = 'version';
        $arrSettings['name'] = 'version';
        $arrSettings['label'] = 'Version';
        $arrSettings['mandatory'] = true;
        $arrSettings['multiple'] = true;
        $arrSettings['options'] = $arrOptions;
        $versionChoice = new \CheckBox($arrSettings);
        $objTemplate->versionChoice = $versionChoice->parse();
        unset($arrSettings);

        $arrOptions = array();
        foreach (deserialize($GLOBALS['TL_CONFIG']['iso_docrobot_languages'], true) as $arrLanguage) {
            $arrOptions[] = array(
                'value' => $arrLanguage['language'],
                'label' => $arrLanguage['language']
            );
        }

        $arrSettings['id'] = 'lang';
        $arrSettings['name'] = 'lang';
        $arrSettings['label'] = 'Sprache';
        $arrSettings['mandatory'] = true;
        $arrSettings['multiple'] = true;
        $arrSettings['options'] = $arrOptions;
        $langChoice = new \CheckBox($arrSettings);
        $objTemplate->langChoice = $langChoice->parse();
        unset($arrSettings);

        $arrOptions = array();
        foreach (trimsplit(',', $GLOBALS['TL_CONFIG']['iso_docrobot_books']) as $strBook) {
            $arrOptions[] = array(
                'value' => $strBook,
                'label' => $strBook
            );
        }

        $arrSettings['id'] = 'book';
        $arrSettings['name'] = 'book';
        $arrSettings['label'] = 'Buch';
        $arrSettings['mandatory'] = true;
        $arrSettings['multiple'] = true;
        $arrSettings['options'] = $arrOptions;
        $bookChoice = new \CheckBox($arrSettings);
        $objTemplate->bookChoice = $bookChoice->parse();
        unset($arrSettings);

        return $objTemplate->parse();
    }
}