<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yanickwitschi
 * Date: 6/14/13
 * Time: 11:48 AM
 * To change this template use File | Settings | File Templates.
 */

namespace IsotopeDocRobot\Maintenance;

use IsotopeDocRobot\Context\Context;
use IsotopeDocRobot\Routing\Routing;
use IsotopeDocRobot\Service\GitHubBookParser;
use IsotopeDocRobot\Service\GitHubCachedBookParser;
use IsotopeDocRobot\Service\GitHubConnector;

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
            foreach (\Input::post('contextType') as $contextType) {
                foreach (\Input::post('version') as $version) {
                    foreach (\Input::post('lang') as $lang) {
                        foreach (\Input::post('book') as $book) {


                            $context = new Context($contextType);
                            $context->setBook($book);
                            $context->setLanguage($lang);
                            $context->setVersion($version);

                            if (\Input::post('fetch') == 'yes') {
                                $connector = new GitHubConnector($context);
                                $connector->purgeCache();
                                $connector->updateAll();
                            }

                            try {
                                $routing = new Routing($context);
                            } catch (\InvalidArgumentException $e) {
                                continue;
                            }

                            $bookParser = new GitHubCachedBookParser(
                                'system/cache/isotope/docrobot',
                                new GitHubBookParser(
                                    $context,
                                    $routing
                                )
                            );

                            $bookParser->purgeCache();
                            $bookParser->parseAllRoutes();
                        }
                    }
                }
            }
        }

        $objTemplate = new \BackendTemplate('be_isotope_docrobot_maintenance');
        $objTemplate->action = ampersand(\Environment::get('request'));
        $objTemplate->headline = specialchars('Isotope DocRobot');

        $arrOptions = array();
        foreach (array('html', 'pdf') as $strContext) {
            $arrOptions[] = array(
                'value' => $strContext,
                'label' => $strContext
            );
        }

        $arrSettings['id'] = 'contextType';
        $arrSettings['name'] = 'contextType';
        $arrSettings['label'] = 'Kontext-Typ';
        $arrSettings['mandatory'] = true;
        $arrSettings['multiple'] = true;
        $arrSettings['options'] = $arrOptions;
        $contextChoice = new \CheckBox($arrSettings);
        $objTemplate->contextChoice = $contextChoice->parse();
        unset($arrSettings);

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
