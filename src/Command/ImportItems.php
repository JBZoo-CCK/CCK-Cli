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
use JBZoo\Utils\Cli;
use JBZoo\Utils\Env;
use JBZoo\Utils\FS;
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
     * @var string
     */
    protected $_tmpFile = '';

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
            )
            ->addOption(
                'stepmode',
                null,
                InputOption::VALUE_NONE,
                'Enable step mode. Each step call itself. Experimental for memory optimization'
            )
            ->addOption(
                'step',
                null,
                InputOption::VALUE_OPTIONAL,
                'Current step number for step mode',
                -1
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

        $this->_tmpFile = JBZOO_CLI_ROOT . '/resources/tmp/session.ser';
        @mkdir(dirname($this->_tmpFile), 0777, true);
    }

    /**
     * Execute method of command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine
    {
        $this->_executePrepare($input, $output);
        $this->_init();

        // Prepare
        if ((int)$this->_config->get('reindex', 0)) {
            $this->_('Database ReIndex = true', 'info');
            $_GET['controller'] = $_REQUEST['controller'] = 'cli-import'; // emulate browser and admin CP
        } else {
            $this->_('Database ReIndex = false', 'info');
            $_GET['controller'] = $_REQUEST['controller'] = 'jbimport'; // emulate browser and admin CP
        }

        $stepMode    = $this->_getOpt('stepmode');
        $step        = $this->_getOpt('step');
        $profileName = $this->_getOpt('profile');

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
        $this->_('Step mode: ' . ($stepMode ? 'on' : 'off'), 'Info');

        // Show progress bar and run process
        $jbimport   = $this->_jbimport;
        $_this      = $this;
        $isFinished = false;

        if ($stepMode) {

            if ($step >= 0) {
                $jbimport->itemsProcess($step);
                $this->_addTmpData($this->_jbsession->get('ids', 'import-ids'));

            } else {

                $this->_cleanTmpData();

                $this->_progressBar(
                    'import',
                    $stepsCount,
                    1,
                    function ($currentStep) use ($jbimport, $stepsCount, $profileName, $_this) {

                        $phpBin  = Env::getBinary();
                        $binPath = './' . FS::getRelative($_SERVER['SCRIPT_FILENAME'], JPATH_ROOT, '/');
                        $options = array(
                            'profile'  => $profileName,
                            'step'     => (int)$currentStep,
                            'stepmode' => '',
                            'q'        => '',
                        );

                        $result = Cli::exec($phpBin . ' ' . $binPath . ' import:items', $options, JPATH_ROOT, false);

                        if (0 && $this->_isDebug()) {
                            $_this->_($result);
                        }

                        return $currentStep <= $stepsCount;
                    }
                );

                $this->_jbsession->set('ids', $this->_getTmpData(), 'import-ids');
                $isFinished = true;
            }

        } else {
            $this->_progressBar('import', $stepsCount, 1, function ($currentStep) use ($jbimport) {
                $result = $jbimport->itemsProcess($currentStep);
                return ($result['progress'] >= 100) ? false : true;
            });

            $isFinished = true;
        }

        if ($isFinished) {
            $this->_showProfiler('Import - finished');

            // Remove or disable other items
            $this->_jbsession->set('ids', $this->_getTmpData(), 'import-ids');
            $this->_postImport();
            $this->_showProfiler('Import - Post handler');

            $this->_moveCsvFile($csvInfo['path']);
            $this->_showProfiler('Import - Done!');

            $this->_cleanTmpData();
        }
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

        $mode = $this->_jbsession->get('lose', 'import');
        if ($mode == 0) {
            $this->_('No post precessing', 'Info');
        } else {
            $this->_('Total items imported: ' . count($addedIds), 'Info');
        }

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

    /**
     * @return array|string
     */
    protected function _getTmpData()
    {
        $data = array();

        if (file_exists($this->_tmpFile)) {
            $data = (array)unserialize(file_get_contents($this->_tmpFile));
        }

        return $data;
    }

    /**
     * @param $data
     */
    protected function _addTmpData($data)
    {
        $prevData = $this->_getTmpData();
        $data     = array_merge((array)$data, (array)$prevData);
        file_put_contents($this->_tmpFile, serialize($data));
    }

    /**
     * Clean tmp file
     */
    protected function _cleanTmpData()
    {
        @unlink($this->_tmpFile);
    }
}
