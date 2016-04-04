<?php
/**
 * JBZoo CCK Cli
 *
 * This file is part of the JBZoo CCK package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package   CCK Cli
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/CCK-Cli
 * @author    Denis Smetannikov <denis@jbzoo.com>
 */

namespace JBZoo\Console\Command;

use JBZoo\Utils\FS;
use JBZoo\Utils\Slug;
use JBZoo\Console\CommandJBZoo;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportYmlItems
 *
 * @package JBZoo\Console\Command
 */
class ExportYmlItems extends CommandJBZoo
{

    /**
     * @var \JBSessionHelper
     */
    protected $_jbsession = null;

    /**
     * @var \JBYmlHelper
     */
    protected $_jbyml = null;

    /**
     * @var \JBConfigHelper
     */
    protected $_jbconfig = null;

    /**
     * Configuration of command.
     *
     * @return void
     */
    protected function configure() // @codingStandardsIgnoreLine
    {
        $this
            ->setName('export:yml-items')
            ->setDescription('Export items for YML file')
            ->addOption(
                'profile',
                null,
                InputOption::VALUE_REQUIRED,
                'Profile name is PHP-file in \'config\' directory like \'export-yml-items-<NAME>.php\'  ',
                'default'
            );
    }

    /**
     * Init Joomla Framework.
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return void
     */
    protected function _loadJoomla()
    {
        define('_JEXEC', 1);
        define('JDEBUG', 0); // Exclude Joomla Debug Mode from JBZoo Cli. Cause it has some bugs
        define('JPATH_BASE', JBZOO_CLI_JOOMLA_ROOT); // website root directory

        require_once JPATH_BASE . '/includes/defines.php';
        require_once JPATH_BASE . '/includes/framework.php';

        // prepare env (emulate browser)
        $this->_browserEmulator();

        if (!$_SERVER['HTTP_HOST']) {
            $this->_('Host is undefined. Please, check global config "./config/_global.php"', 'Error');
            $this->_('Joomla need it for browser simulation', 'Error', 1);
        }

        // no output
        $_GET['tmpl'] = $_REQUEST['tmpl'] = 'raw';
        $_GET['lang'] = $_REQUEST['lang'] = 'ru';

        // init Joomla App ( Front-end emulation )
        \JFactory::getApplication('site');
    }

    /**
     * {@inheritdoc}
     */
    protected function _init()
    {
        parent::_init();

        $this->_jbyml     = $this->app->jbyml;
        $this->_jbconfig  = $this->app->jbconfig;
        $this->_jbsession = $this->app->jbsession;
    }

    /**
     * @return \JBModelConfig
     */
    protected function _setupConfigure()
    {
        $configModel = \JBModelConfig::model();
        $configModel->setGroup('config.yml', array(
            'site_url'      => rtrim(\JUri::root(), '/'),
            'file_path'     => $this->_getFilePath(),
            'file_name'     => $this->_getProfFileName(),
            'site_name'     => $this->_config->find('params.site_name', 'My site name'),
            'company_name'  => $this->_config->find('params.company_name', 'My company name'),
            'currency_rate' => $this->_config->find('params.currency_rate', 'default'),
        ));

        $configModel->set('app_list', $this->_config->find('params.app_list', array(1)));
        $configModel->set('type_list', $this->_config->find('params.type_list', array('product')));

        return $configModel;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine
    {
        $this->_executePrepare($input, $output);

        $this->_init();
        $this->_setupConfigure();

        $this->_jbyml->init();
        $totalItems = $this->_jbyml->getTotal();
        $filePath   = $this->_getFilePath() . '/' . $this->_getProfFileName() . '.xml';
        $fullPath   = FS::clean(JBZOO_CLI_JOOMLA_ROOT . '/' . $filePath);

        $this->_showProfiler('YML Export - prepared');
        $this->_('YML File: ' . $fullPath, 'Info');
        $this->_('Total items: ' . $totalItems, 'Info');

        $this->_jbyml->renderStart();

        $this->_progressBar('yml-export', $totalItems, 1, function ($currentStep) {
            $totalItems = $this->_jbyml->getTotal();
            if ($currentStep <= $totalItems) {
                $this->_jbyml->exportItems($currentStep, $currentStep + 1);
            }

            return true;
        });

        $this->_jbyml->renderFinish();
    }

    /**
     * @return mixed
     */
    protected function _getSiteUrl()
    {
        return $this->_globConfig->get('host');
    }

    /**
     * @return mixed|string
     */
    protected function _getFileName()
    {
        return $this->_config->find('params.file_name', 'yml');
    }

    /**
     * @return string
     */
    protected function _getProfFileName()
    {
        return Slug::filter($this->_getFileName() . '-' . $this->_getOpt('profile'));
    }

    /**
     * @return mixed|string
     */
    protected function _getFilePath()
    {
        return $this->_config->find('params.file_path', 'cli/jbzoo/resources/sources');
    }

    /**
     * @return void
     */
    protected function _browserEmulator()
    {
        $_SERVER['SCRIPT_NAME']   = '/index.php';
        $_SERVER['REQUEST_URI']   = '/index.php';
        $_SERVER['PHP_SELF']      = '/index.php';
        $_SERVER['HTTP_HOST']     = $this->_getSiteUrl();
        $_SERVER['SERVER_NAME']   = $this->_getSiteUrl();
        $_SERVER['DOCUMENT_ROOT'] = JBZOO_CLI_JOOMLA_ROOT;
    }
}
