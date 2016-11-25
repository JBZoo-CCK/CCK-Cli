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

defined('JBZOO_CLI') or die;

// putenv('PHP_BINARY_CUSTOM="/usr/bin/php5.6 -c /etc/php/apache2-php5.6/php.ini"');

return array(
    'host'   => '', // "my-current-site.com"
    'memory' => '512M',
    'time'   => 1800,
    'auth'   => array(
        'login'     => 'admin',
        'pass'      => '123456',
        'secretkey' => '', // usually it's empty
    ),
);
