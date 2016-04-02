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
        $siteUrl = $this->_config->find('params.site_url', 'my-site.ru');

        $configModel->setGroup('config.yml', array(
            'site_url'      => $siteUrl,
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
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine
    {
        $this->_executePrepare($input, $output);

        $this->_browserEmulator();
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
     * @return void
     */
    protected function _browserEmulator()
    {
        // Web-server emulator
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['SERVER_NAME']     = 'jbzoo220.realty';
        $_SERVER['DOCUMENT_ROOT']   = realpath('.');
        // Local host
        $_SERVER['REMOTE_ADDR']     = '127.0.0.1';
        $_SERVER['SERVER_PORT']     = '80';
        $_SERVER['REMOTE_PORT']     = '54778';
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.2.29';
        // HTTP headers
        $_SERVER['HTTP_HOST']            = 'crosscms.com';
        $_SERVER['HTTP_ACCEPT']          = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $_SERVER['HTTP_USER_AGENT']      = 'JBZoo PHPUnit Tester';
        $_SERVER['HTTP_CONNECTION']      = 'keep-alive';
        $_SERVER['HTTP_CACHE_CONTROL']   = 'max-age=0';
        $_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate, sdch';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4';
        // request
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = 'jbzoo220.realty';
        $_SERVER['QUERY_STRING']   = '';
    }
}
