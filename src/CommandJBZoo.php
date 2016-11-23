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

namespace JBZoo\Console;

use JBZoo\Utils\FS;
use JBZoo\Data\Data;
use JBZoo\Utils\Cli;
use JBZoo\Utils\Env;
use JBZoo\Utils\Sys;
use JBZoo\Data\PHPArray;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CommandJBZoo
 * @package JBZoo\Console
 * @codeCoverageIgnore
 */
class CommandJBZoo extends Command
{
    /**
     * @var Data
     */
    protected $_globConfig;

    /**
     * @var Data
     */
    protected $_config;

    /**
     * @var \App
     */
    protected $app; // @codingStandardsIgnoreLine

    /**
     * Init all
     */
    protected function _init()
    {
        $this->_showProfiler('Start');
        $this->_initGlobaConfig();
        $this->_loadSystem();
        $this->_loadJoomla();

        $this->_showProfiler('Joomla loaded');

        $this->_loadJBZoo();
        $this->_showProfiler('JBZoo loaded');

        $this->_initCurrentConfig();
        $this->_setEnv();
        $this->_userAuth();

        $this->_showProfiler('Tool is ready');
    }

    /**
     * Define system variables
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function _loadSystem()
    {
        !defined('DS') && define('DS', DIRECTORY_SEPERATOR);
    }

    /**
     * Init Joomla Framework
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function _loadJoomla()
    {
        define('_JEXEC', 1);
        define('JPATH_BASE', JBZOO_CLI_JOOMLA_ROOT); // website root directory

        $_SERVER['SCRIPT_NAME'] = JPATH_BASE . '/administrator/index.php'; // Joomla Enviroment mini-hack

        require_once JPATH_BASE . '/includes/defines.php';
        require_once JPATH_BASE . '/includes/framework.php';

        // prepare env (emulate browser)
        $_SERVER['HTTP_HOST']      = $this->_globConfig->get('host');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        if (!$_SERVER['HTTP_HOST']) {
            $this->_('Host is undefined. Please, check global config "./config/_global.php"', 'Error');
            $this->_('Joomla need it for browser simulation', 'Error', 1);
        }

        // no output
        $_GET['tmpl'] = $_REQUEST['tmpl'] = 'raw';
        $_GET['lang'] = $_REQUEST['lang'] = 'ru';

        // init Joomla App ( Front-end emulation )
        \JFactory::getApplication('site');
        //\JFactory::getApplication('administrator'); // or Admin Application
    }

    /**
     * Init JBZoo Framework
     */
    protected function _loadJBZoo()
    {
        define('JBZOO_APP_GROUP', 'jbuniversal');

        // include Zoo & JBZoo
        require_once JPATH_BASE . '/administrator/components/com_zoo/config.php';
        require_once JPATH_BASE . '/media/zoo/applications/jbuniversal/framework/jbzoo.php';

        \JBZoo::init();

        $this->app = \App::getInstance('zoo');
        
        $lang = \JFactory::getLanguage();
        $lang->setDefault('ru-RU');
        $lang->load('com_jbzoo', $this->app->path->path('jbapp:'), 'ru-RU', true);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function _userAuth()
    {
        $isAuth = \JFactory::getApplication()->login(array(
            'username'  => $this->_globConfig->find('auth.login'),
            'password'  => $this->_globConfig->find('auth.pass'),
            'secretkey' => $this->_globConfig->find('auth.secretkey'),
        ));

        if (!$isAuth) {
            throw new \Exception('Can\'t login as admin!');
        }

        return true;
    }

    /**
     * @return int
     */
    protected function _setEnv()
    {
        // set limits & reporting
        if ($this->_isDebug()) {
            error_reporting(-1);
        } else {
            error_reporting(E_ERROR | E_WARNING);
        }

        $memory = $this->_globConfig->get('memory', '1024M');
        $time   = (int)$this->_globConfig->get('time', 1800);

        Sys::iniSet('display_errors', 1);
        Sys::setTime($time);
        Sys::setMemory($memory);
    }

    /**
     * @return int
     */
    protected function _isDebug()
    {
        return $this->_out->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE;
    }

    /**
     * Load global config
     */
    protected function _initGlobaConfig()
    {
        $configPath = JBZOO_CLI_ROOT . '/configs/_global.php';

        if ($path = FS::real($configPath)) {
            $this->_globConfig = new PHPArray($path);
        } else {
            throw new Exception('Global config file "' . $configPath . '" not found');
        }
    }

    /**
     * Load current config
     */
    protected function _initCurrentConfig()
    {
        $this->_config = new PHPArray();

        $isProfile = $this->_in->hasOption('profile');

        $configName = str_replace(':', '-', strtolower($this->_in->getFirstArgument()));
        $configName = $isProfile ? $configName . '-' . $this->_getOpt('profile') : $configName;
        $configPath = JBZOO_CLI_ROOT . '/configs/' . $configName . '.php';

        if ($path = FS::real($configPath)) {
            $this->_config = new PHPArray($path);

        } elseif ($isProfile) {
            throw new Exception('Config file "' . $configPath . '" not found');
        }
    }

    /**
     * @param string $messages The message as an array of lines of a single string
     * @param string $type
     * @param int    $isDieCode
     */
    protected function _($messages, $type = '', $isDieCode = 0)
    {
        $type = strtoupper($type);

        if ($type == 'INFO') {
            $messages = '<info>' . $type . ':</info> ' . $messages;

        } elseif ($type == 'ERROR') {
            $messages = '<error>' . $type . ':</error> ' . $messages;

        } elseif ($type == 'COMMENT') {
            $messages = ' - ' . $messages;
        }

        $this->_out->writeln($messages);

        if ($isDieCode > 0) {
            exit((int)$isDieCode);
        }
    }

    protected function _showProfiler($label)
    {
        if (!$this->_isDebug()) {
            return false;
        }

        // memory
        $memoryCur = round(memory_get_usage(false) / 1024 / 1024, 2);
        $memoryCur = sprintf("%.02lf", round($memoryCur, 2));

        $memoryPeak = round(memory_get_peak_usage(false) / 1024 / 1024, 2);
        $memoryPeak = sprintf("%.02lf", round($memoryPeak, 2));

        // time
        $time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $time = sprintf("%.02lf", round($time, 2));

        // Show
        $message = array(
            'Memory: ' . $memoryCur . 'MB',
            'Mem.Peak: ' . $memoryPeak . 'MB',
            "Time: " . $time . 's',
            $label ? '(' . $label . ')' : ' ',
        );

        $this->_(implode("  |  ", $message), 'Info');
    }

    /**
     * Show progress bar and run the loop
     *
     * @param string   $name
     * @param int      $total
     * @param int      $stepSize
     * @param \Closure $callback
     */
    protected function _progressBar($name, $total, $stepSize, $callback)
    {
        $this->_('Current progress of ' . $name . ' (Wait! or `Ctrl+C` to cancel):');

        $progressBar = new ProgressBar($this->_out, $total);
        $progressBar->display();
        $progressBar->setRedrawFrequency(1);

        for ($currentStep = 0; $currentStep <= $total; $currentStep += $stepSize) {

            $callbackResult = $callback($currentStep, $stepSize);

            if ($callbackResult === false) {
                break;
            }

            $progressBar->setProgress($currentStep);
        }

        $progressBar->finish();
        $this->_(''); // Progress bar hack for rendering
    }

    /**
     * Progress wrapper (if use stepmode).
     *
     * @param string $name
     * @param int $total
     * @param int $stepSize
     * @param \Closure $onStart
     * @param \Closure $onStepMode
     * @param \Closure $onFinish
     */
    protected function _progressWrap($name, $total, $stepSize, $onStart, $onStepMode, $onFinish)
    {
        $_this    = $this;
        $step     = $this->_getOpt('step');
        $profile  = $this->_getOpt('profile');
        $stepMode = $this->_getOpt('stepmode');

        $isFinished = false;
        if ($stepMode) {
            if ($step >= 0) {
                $onStepMode($step);
            } else {
                $this->_progressBar(
                    $name,
                    $total,
                    $stepSize,
                    function ($currentStep) use ($profile, $total, $_this, $name) {
                        $phpBin  = Env::getBinary();
                        $binPath = './' . FS::getRelative($_SERVER['SCRIPT_FILENAME'], JPATH_ROOT, '/');
                        $options = array(
                            'profile'  => $profile,
                            'step'     => (int) $currentStep,
                            'stepmode' => '',
                            'q'        => '',
                        );

                        $command = $phpBin . ' ' . $binPath . ' ' . $name;
                        $result  = Cli::exec($command, $options, JPATH_ROOT, false);

                        if (0 && $this->_isDebug()) {
                            $_this->_($result);
                        }

                        return $currentStep <= $total;
                    }
                );

                $isFinished = true;
            }
        } else {
            $isFinished = $onStart();
        }

        $onFinish($isFinished);
    }
}
