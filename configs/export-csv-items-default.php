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

defined('JBZOO_CLI') or die;

return array(
    'params'     => array(
        'application'       => 1,               //  Application id,

        'category'          => '-1',            //  Category
                                                //  -1 - All categories
                                                //  0 - Title page
                                                //  {n > 0} - Category id
        'category_nested'   => true,
        'item_type'         => 'product',       //  Item types
        'state'             => '0',             //  Item state. 0 - all
                                                //  1 - Just published, taking into account the date of publication
                                                //  2 - Just published
                                                //  3 - Just not published

        'fields_core'       => true,            //  For example, name, alias, ID, category and others
        'fields_user'       => true,            //  The value of all custom fields item
        'fields_config'     => true,            //  For example, publication status, setting comments and others.
        'fields_meta'       => true,            //  Meta data (description, keywords, robots, title)
        'fields_price'      => false,           //  These JBPrice element, broken down by individual columns.
        'fields_full_price' => true,            //  Uploaded data JBPrice in full format.
        'merge_repeatable'  => false,           //  Combine repeatable field.

        'item_sort'         => 'id',            //  Materials sort in csv file.
                                                //  priority - By priority
                                                //  id - By id
                                                //  alpha - By name
                                                //  alias - By alias
                                                //  hits - By hits
                                                //  date - By creation date
                                                //  mdate - By modification date
                                                //  publish_up - By publish date
                                                //  publish_down - By publish down date
                                                //  author - By author

        'reverse'           => false,           //  Reverse item order
        'separator'         => ';',
        'enclosure'         => '"',
        'step_limit'        => 100,
        'file_path'         => 'cli/jbzoo/resources/sources',
    ),
);
