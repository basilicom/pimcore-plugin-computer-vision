<?php

namespace ComputerVision;

use ComputerVision\Command\AnalyzeCommand;
use Pimcore\API\Plugin as PluginLib;

class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface
{
    const SUBCRIPTIONKEY_DUMMY = '__ADD_YOUR_KEY_HERE__';

    public function init()
    {
        parent::init();
        \Pimcore::getEventManager()->attach('system.console.init', array($this, 'attachConsoleCommand'));
    }

    public static function install()
    {
        $config = new \Zend_Config(array(), true);
        $config->computerVisionSubscriptionKey = self::SUBCRIPTIONKEY_DUMMY;

        $configWriter = new \Zend_Config_Writer_Xml();
        $configWriter->setConfig($config);
        $configWriter->write(self::getConfigName());
        return 'Plugin successfully installed';
    }
    
    public static function needsReloadAfterInstall() {
        return true; 
    }
    
    public static function uninstall()
    {
        if (file_exists(self::getConfigName())) {
            unlink(self::getConfigName());
        }

        return 'Plugin successfully un-installed';
    }

    public static function isInstalled()
    {
        if (file_exists(self::getConfigName())) {
            return true;
        }

        return false;
    }

    public static function getConfigName()
    {
        return PIMCORE_WEBSITE_PATH
        . '/var/config/'
        . 'extension-'
        . str_replace('\plugin', '', strtolower(__CLASS__))
        . '.xml';
    }

    public function attachConsoleCommand($e)
    {
        /** @var \Pimcore\Console\Application $application */
        $application = $e->getTarget();
        $application->addAutoloadNamespace(
            'ComputerVision\\Command',
            PIMCORE_DOCUMENT_ROOT . '/plugins/ComputerVision/lib/ComputerVision/Command'
        );
        $application->add(new AnalyzeCommand());
    }
}
