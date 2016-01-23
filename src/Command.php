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

namespace JBZoo\Cli;

use JBZoo\Console\Command as JBZooCommand;
use JBZoo\Data\Data;
use JBZoo\Data\PHPArray;
use JBZoo\Utils\FS;
use JBZoo\Utils\OS;

/**
 * Class Command
 * @package JBZoo\Cli
 * @codeCoverageIgnore
 */
class Command extends JBZooCommand
{
    /**
     * @var Data
     */
    protected $_config;

    /**
     * Define system variables
     */
    protected function _loadSystem()
    {
        !defined('DIRECTORY_SEPERATOR') && define('DIRECTORY_SEPERATOR', '/');
        !defined('DS') && define('DS', DIRECTORY_SEPERATOR);

        $_SERVER['SCRIPT_NAME'] = __FILE__; // Joomla Enviroment mini-hack
    }

    /**
     * Init Joomla Framework
     */
    protected function _loadJoomla()
    {
        define('_JEXEC', 1);
        define('JDEBUG', 0); // Exclude Joomla Debug Mode from JBZoo Cli. Cause it has some bugs
        define('JPATH_BASE', JBZOO_CLI_JOOMLA_ROOT); // website root directory

        require_once JPATH_BASE . '/includes/defines.php';
        require_once JPATH_LIBRARIES . '/import.legacy.php';
        require_once JPATH_LIBRARIES . '/cms.php';

        // prepare env (emulate browser)
        $_SERVER['HTTP_HOST']      = $this->_config->get('host');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // no output
        $_GET['tmpl'] = $_REQUEST['tmpl'] = 'raw';
        $_GET['lang'] = $_REQUEST['lang'] = 'ru';

        // init Joomla App ( Front-end emulation )
        \JFactory::getApplication('site');
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
    }

    /**
     * Init all
     */
    protected function _init()
    {
        $this->_initGlobaConfig();
        $this->_loadSystem();
        $this->_loadJoomla();
        $this->_loadJBZoo();
        $this->_setEnv();
        $this->_userAuth();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function _userAuth()
    {
        $isAuth = \JFactory::getApplication()->login(array(
            'username'  => $this->_config->find('auth.login'),
            'password'  => $this->_config->find('auth.pass'),
            'secretkey' => $this->_config->find('auth.secretkey'),
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
            error_reporting(E_ERROR || E_WARNING);
        }

        $memory = $this->_config->get('memory', '512M');
        $time   = (int)$this->_config->get('time', 1800);

        OS::iniSet('memory_limit', $memory);
        OS::iniSet('display_errors', 1);
        OS::iniSet('max_execution_time', $time);
        if (function_exists('set_time_limit')) {
            @set_time_limit($time);
        }
    }

    /**
     * @return int
     */
    protected function _isDebug()
    {
        return (int)$this->_config->get('debug', 0);
    }

    /**
     * Load global config
     */
    protected function _initGlobaConfig()
    {
        if ($path = FS::real(JBZOO_CLI_ROOT . '/configs/global.php')) {
            $this->_config = new PHPArray($path);
        } else {
            $this->_config = new PHPArray();
        }
    }
}
