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

return array(
    'debug'   => 1,
    'host'    => 'example.com',

    'auth'    => array(
        'login'     => 'admin',
        'pass'      => '123456',
        'secretkey' => '', // usually it's empty
    ),

    'reindex' => array(
        'step' => 10,
    ),
);
