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
 * @author    Sergey Kalistratov <kalistratov.s.m@gmail.com>
 */

namespace JBZoo\Console\Command;

use JBZoo\Utils\FS;
use JBZoo\Utils\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExportCsvItems
 *
 * @package JBZoo\Console\Command
 */
class ExportCsvItems extends ExportYmlItems
{

    /**
     * @var \JBExportHelper
     */
    protected $_jbexport = null;

    /**
     * @var \JBPathHelper
     */
    protected $_jbpath = null;

    /**
     * @var \JBSessionHelper
     */
    protected $_jbsession = null;

    /**
     * @var string
     */
    protected $_commandName = 'export:csv-items';

    /**
     * Get current app id and category list.
     *
     * @return array
     */
    protected function _getCategoryList()
    {
        $config = $this->_jbconfig->getList('export.items');
        list ($appId, $categoryList) = explode(':', $this->_getItemsAppCategory());
        $categoryList = (array) $categoryList;

        // get full category list
        if ((int) $config->get('category_nested', 0) && !in_array('-1', $categoryList)) {
            $categoryList = \JBModelCategory::model()->getNestedCategories($categoryList);
        }

        return array($appId, $categoryList);
    }

    /**
     * @return string
     */
    protected function _getItemsAppCategory()
    {
        $app = Str::trim($this->_config->find('params.application', 1));
        $cat = Str::trim($this->_config->find('params.category', '-1'));
        return $app . ':' . $cat;
    }

    /**
     * Get total items.
     *
     * @return int
     */
    protected function _getTotal()
    {
        $config = $this->_jbconfig->getList('export.items');
        list ($appId, $catList) = $this->_getCategoryList();
        return \JBModelItem::model()->getTotal($appId, $config->get('item_type'), $catList, $config->get('state', 0));
    }

    /**
     * {@inheritdoc}
     */
    protected function _init()
    {
        parent::_init();

        $this->_jbpath    = $this->app->jbpath;
        $this->_jbexport  = $this->app->jbexport;
        $this->_jbconfig  = $this->app->jbconfig;
        $this->_jbsession = $this->app->jbsession;
    }

    /**
     * Setup configuration.
     *
     * @return \JBModelConfig
     */
    protected function _setupConfigure()
    {
        $configModel = \JBModelConfig::model();
        $configModel->setGroup('export.items', array(
            'application'       => $this->_config->find('params.application', 1),
            'category'          => $this->_config->find('params.category', '-1'),
            'separator'         => $this->_config->find('params.separator', ';'),
            'enclosure'         => $this->_config->find('params.enclosure', '"'),
            'item_sort'         => $this->_config->find('params.item_sort', 'id'),
            'step'              => $this->_config->find('params.step_limit', '100'),
            'state'             => $this->_config->find('params.category_nested', 0),
            'reverse'           => (int) $this->_config->find('params.reverse', false),
            'item_type'         => $this->_config->find('params.item_type', 'product'),
            'fields_core'       => (int) $this->_config->find('params.fields_core', true),
            'fields_user'       => (int) $this->_config->find('params.fields_user', true),
            'fields_meta'       => (int) $this->_config->find('params.fields_meta', true),
            'fields_price'      => (int) $this->_config->find('params.fields_price', false),
            'fields_config'     => (int) $this->_config->find('params.fields_config', true),
            'category_nested'   => (int) $this->_config->find('params.category_nested', true),
            'fields_full_price' => (int) $this->_config->find('params.fields_full_price', true),
            'merge_repeatable'  => (int) $this->_config->find('params.merge_repeatable', false),
            'file_path'         => $this->_config->find('params.file_path', 'cli/jbzoo/resources/sources'),
        ));

        return $configModel;
    }

    /**
     * Process items to CSV.
     *
     * @param $step
     * @return void
     */
    protected function _toCSV($step)
    {
        $config = $this->_jbconfig->getList('export.items');
        $config->set('limit', array($step * $config->get('step'), $config->get('step')));
        $this->_jbexport->itemsToCSV($config->get('appId'), $config->get('catId'), $config->find('req.type'), $config);
    }

    /**
     * Configuration of command.
     *
     * @return void
     */
    protected function configure() // @codingStandardsIgnoreLine
    {
        $this
            ->setName($this->_commandName)
            ->setDescription('Export items to CSV file')
            ->addOption(
                'profile',
                null,
                InputOption::VALUE_REQUIRED,
                'Profile name is PHP-file in \'config\' directory like \'export-csv-items-<NAME>.php\'  ',
                'default'
            )->addOption(
                'stepmode',
                null,
                InputOption::VALUE_NONE,
                'Enable step mode. Each step call itself. Experimental for memory optimization'
            )
            ->addOption(
                'step',
                null,
                InputOption::VALUE_OPTIONAL,
                'Current step number for step mode (Set automatically if enabled stepmode)',
                -1
            );
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

        $stepMode    = $this->_getOpt('stepmode');
        $profileName = $this->_getOpt('profile');
        $config      = $this->_jbconfig->getList('export.items');
        $stepSize    = (int) $this->_config->find('params.step_limit', '100');
        $totalItems  = $this->_getTotal();

        $this->_showProfiler('CSV Export - prepared');
        $this->_('Step size: ' . $stepSize, 'Info');
        $this->_('Total items: ' . $totalItems, 'Info');
        $this->_('Step mode: ' . ($stepMode ? 'on' : 'off'), 'Info');

        $this->_progressWrap(
            $this->_commandName,
            $totalItems,
            $stepSize,

            //  On start.
            function () use ($config, $totalItems, $stepSize) {
                $this->_jbexport->clean();
                $this->_progressBar($this->_commandName, $totalItems, $stepSize, function ($currentStep) use ($config) {
                    $this->_toCSV($currentStep);
                });

                return true;
            },

            // On step.
            function ($step) use ($config) {
                $this->_toCSV($step);
            },

            // On finish.
            function ($isFinished) use ($config, $profileName) {
                if ($isFinished && $compressFiles = $this->_jbexport->splitFiles()) {
                    $exportPath = $this->_jbpath->sysPath('tmp', '/' . \JBExportHelper::EXPORT_PATH);

                    //  Move CSV file to config path.
                    foreach ($compressFiles as $file) {
                        $fileName = basename($file);
                        $dir      = JBZOO_CLI_JOOMLA_ROOT . '/' . $config->get('file_path');
                        $newFile  = FS::clean($dir . '/' . $profileName . '-' . $fileName, '/');
                        \JFile::move($file, $newFile);
                        $this->_('CSV File: ' . $newFile, 'Info');
                    }

                    //  Delete export tmp folder.
                    if (\JFolder::exists($exportPath)) {
                        \JFolder::delete($exportPath);
                    }
                }

                $this->_showProfiler('Export - finished');
            }
        );
    }
}
