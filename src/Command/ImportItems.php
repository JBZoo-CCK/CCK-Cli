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

use JBZoo\Console\CommandJBZoo;
use JBZoo\Data\Data;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ImportItems
 * @package JBZoo/Console
 */
class ImportItems extends CommandJBZoo
{
    /**
     * @var \JBImportHelper
     */
    protected $_jbimport = null;

    /**
     * @var \JBSessionHelper
     */
    protected $_jbsession = null;

    /**
     * Configuration of command
     */
    protected function configure() // @codingStandardsIgnoreLine
    {
        $this
            ->setName('import:items')
            ->setDescription('Import items from CSV file')
            ->addOption(
                'profile',
                null,
                InputOption::VALUE_REQUIRED,
                'Profile name is PHP-file in \'config\' directory like \'import-items-<NAME>.php\'  ',
                'default'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function _init()
    {
        parent::_init();

        $this->_jbimport  = $this->app->jbimport;
        $this->_jbsession = $this->app->jbsession;
    }

    /**
     * Execute method of command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine
    {
        $this->_executePrepare($input, $output);
        $this->_init();

        // Prepare
        if ((int)$this->_config->get('reindex', 0)) {
            //$this->_('Database ReIndex = true', 'info');
            $_GET['controller'] = $_REQUEST['controller'] = 'jbimport'; // emulate browser and admin CP
        }

        $csvInfo    = $this->_getCsvInfo();
        $sesData    = $this->_initJoomlaSession($csvInfo);
        $stepSize   = $this->_getStepSize();
        $rowsCount  = $sesData['count'];
        $stepsCount = (int)ceil($rowsCount / $stepSize);

        if ($stepsCount <= 0) {
            $this->_('Count of steps is <= 0', 'Error', 1);
        }

        $this->_showProfiler('Import - prepared');

        $this->_('CSV File: ' . $csvInfo['path'], 'Info');
        $this->_('CSV lines: ' . $csvInfo['count'], 'Info');
        $this->_('Step size: ' . $stepSize, 'Info');
        $this->_('Steps count: ' . $stepsCount, 'Info');

        // Show progress bar and run process
        $jbimport = $this->_jbimport;
        $this->_progressBar('import', $csvInfo['count'], $stepSize, function ($currentStep) use ($jbimport) {
            $result = $jbimport->itemsProcess($currentStep);
            return ($result['progress'] >= 100) ? false : true;
        });
        $this->_showProfiler('Import - finished');

        // Remove or disable other items
        $this->_postImport();
        $this->_showProfiler('Import - Post handler');

        $this->_moveCsvFile($csvInfo['path']);
        $this->_showProfiler('Import - Done!');
    }

    /**
     * Check CSV-file (isExists & isEmpty)
     * @return array
     */
    protected function _getCsvInfo()
    {
        $csvFile = \JPath::clean($this->_config->get('source'));
        if (!\JFile::exists($csvFile)) {
            $this->_('CSV file not found: "' . $csvFile . '"', 'error', 1);
        }

        // Get CSV-file info
        $fileInfo = $this->_jbimport->getInfo($csvFile, array(
            'header'    => $this->_config->find('csv.header', 1),
            'separator' => $this->_config->find('csv.separator', ','),
            'enclosure' => $this->_config->find('csv.enclosure', '"'),
        ));

        if ($fileInfo['count'] <= 0) {
            $this->_('Rows not found in CSV-file', 'Error', 1);
        }

        if ($fileInfo['columns'] <= 0) {
            $this->_('Columns not found in CSV-file', 'Error', 1);
        }

        $fileInfo['path'] = $csvFile;

        return $fileInfo;
    }

    protected function _initJoomlaSession($fileInfo)
    {
        $sesData = array(
            // CSV Parser params
            'file'         => $fileInfo['path'],
            'count'        => $fileInfo['count'],
            'header'       => $this->_config->find('csv.header', 1),
            'separator'    => $this->_config->find('csv.separator', ','),
            'enclosure'    => $this->_config->find('csv.enclosure', '"'),

            // Import params
            'import-type'  => 'items',
            'step'         => $this->_getStepSize(),
            'appid'        => $this->_config->find('params.appid', '1'),
            'typeid'       => $this->_config->find('params.typeid', 'product'),
            'lose'         => $this->_config->find('params.lose', '0'),
            'key'          => $this->_config->find('params.key', '0'),
            'create'       => $this->_config->find('params.create', '0'),
            'checkOptions' => $this->_config->find('params.checkOptions', '1'),
            'createAlias'  => $this->_config->find('params.createAlias', '0'),

            // Fields map (CSV => Item type)
            'assign'       => $this->_config->find('assign', array()),
        );

        $this->_jbsession->setGroup($sesData, 'import');

        if ($this->_isDebug()) {
            $this->_('Current import params: ' . print_r($sesData, true), 'Info');
        }

        return new Data($sesData);
    }

    /**
     * @return int
     */
    protected function _getStepSize()
    {
        return (int)$this->_config->find('step', 1);
    }

    /**
     * @return bool
     */
    protected function _postImport()
    {
        $addedIds = (array)$this->_jbsession->get('ids', 'import-ids');
        $addedIds = array_filter($addedIds);

        $this->_jbsession->set('ids', $addedIds, 'import-ids');

        @$this->_jbimport->itemsPostProcess();

        return true;
    }

    /**
     * Move current file to used folder
     * @param string $csvFile
     * @return bool
     */
    protected function _moveCsvFile($csvFile)
    {
        $usedDir = $this->_config->find('used');
        if (!$usedDir) {
            return false;
        }

        $dstFile = $usedDir . '/' . pathinfo($csvFile, PATHINFO_FILENAME) . '_' . date('Y-m-d_H-i-s') . '.csv';
        $dstFile = \JPath::clean($dstFile);

        if (\JFile::move($csvFile, $dstFile)) {
            $this->_('CSV file moved to: ' . $dstFile, 'Info');
        } else {
            $this->_('Couldn\'t move CSV file to: ' . $dstFile, 'Error');
        }

        return true;
    }
}
