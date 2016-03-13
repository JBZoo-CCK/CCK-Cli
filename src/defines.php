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

use JBZoo\Utils\FS;

// Define important constatns
define('JBZOO_CLI', true); // simple security
define('JBZOO_CLI_ROOT', FS::real(__DIR__ . '/../'));
!defined('DIRECTORY_SEPERATOR') && define('DIRECTORY_SEPERATOR', '/');

// Try to find Joomla root path
$jrootPath   = FS::real(__DIR__ . '/../../../');
$jconfigPath = FS::real($jrootPath . '/configuration.php');
$path        = $jconfigPath ? $jrootPath : getenv('JOOMLA_DEV_PATH'); // placeholder for developer

if (!$path) {
    throw new Exception('Joomla Root Path is not found!');
}
define('JBZOO_CLI_JOOMLA_ROOT', FS::real($path));
