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
    'source'     => JPATH_BASE . '/cli/jbzoo/resources/sources/items-import.csv',   // Try to find source file on start
    'used'       => JPATH_BASE . '/cli/jbzoo/resources/used',                       // Move already used csv-files
    'step'       => 100,                                                            // Step size

    'reindex'    => 1,  // Reindex database while import process
                        // 0 - Recomend to reindex database after import
                        // 1 - Need more time and memory

    'csv'        => array(
        'enclosure' => '"', // For example:
        'separator' => ';', // Header:       "Item name";"Alias";"Price"
        'header'    => 1,   // Rows:         "Pretty doll #2579";"pretty-doll-2579";"100500 USD"
    ),

    'params'     => array(
        'appid'        => '1',          // Application ID

        'typeid'       => 'product',    // Item type alias

        'key'          => '4',          // What is considered to be a key?
                                        // 0 - Don't search by key and create new items
                                        // 1 - ID (number)
                                        // 2 - Name
                                        // 3 - Alias
                                        // 4 - SKU from JBPrice

        'create'       => '1',          // Create new item if the key was not found (0/1)

        'checkOptions' => '1',          // Fill the radio, select, checkbox (0/1)

        'lose'         => '2',          // The notes not found in the file
                                        // 0 - Don't change
                                        // 1 - Disable
                                        // 2 - Remove

        'createAlias'  => '1',          // Auto create alias from item name for new items
                                        // 0 - Don't change
                                        // 1 - If empty create from name

        'cleanPrice'   => '0',          // Clean all price info in item before import (0/1)
    ),

    'assign'     => array(

        // See examples in MySQL table `#__zoo_jbzoo_config` => `import.last.items`
        // `key`='<ITEM_ALIAS>' and `value` is JSON of fields assign

        // Examples
        // 'Column number (zero is first)' => 'Element Key'

        // Basic
        '0'  => 'id',                                                           // Item ID
        '1'  => 'name',                                                         // Name
        '2'  => 'alias',                                                        // Item Alias

        // Core
        '3'  => 'author',                                                       // Author
        '4'  => 'created',                                                      // Creation date
        '5'  => 'category',                                                     // Category
        '6'  => 'tags',                                                         // Tags

        // Configuration
        '7'  => 'state',                                                        // Status
        '8'  => 'priority',                                                     // Priority
        '9'  => 'access',                                                       // Access
        '10' => 'searchable',                                                   // Being searched
        '11' => 'publish_up',                                                   // Start date of publishing
        '12' => 'publish_down',                                                 // End date of publishing
        '13' => 'comments',                                                     // Comments
        '14' => 'frontpage',                                                    // On the main page
        '15' => 'category_primary',                                             // Main category
        '16' => 'teaser_image_align',                                           // Image alignment in the announcement
        '17' => 'full_image_align',                                             // Image alignment in the detailed view
        '18' => 'related_image_align',                                          // Image alignment in the relation to
        '19' => 'subcategory_image_align',                                      // Image alignment in subcategories

        // Meta
        '20' => 'hits',                                                         // Browsing amount
        '21' => 'metadata_title',                                               // Heading
        '22' => 'metadata_description',                                         // Description
        '23' => 'metadata_keywords',                                            // Keywords
        '24' => 'metadata_robots',                                              // Robots
        '25' => 'metadata_author',                                              // Author

        // JBPrice Plain (or Calc)
        '26' => 'jbpricecalc__<ELEMENT_ID>___value',                            // Price value
        '27' => 'jbpriceplain__<ELEMENT_ID>___sku',                             // Item SKU
        '28' => 'jbprice<PRICE_TYPE>__<ELEMENT_ID>___discount',                 // Discount
        '29' => 'jbprice<PRICE_TYPE>__<ELEMENT_ID>___balance',                  // Balance
        '30' => 'jbprice<PRICE_TYPE>__<ELEMENT_ID>___description',              // Description
        '31' => 'jbprice<PRICE_TYPE>__<ELEMENT_ID>___image',                    // Photo
        '32' => 'jbprice<PRICE_TYPE>__<ELEMENT_ID>__<PRICE_CORE_ELEMENT_ID>',   // Basic price property (core)
        '33' => 'jbprice<PRICE_TYPE>__<ELEMENT_ID>__<PRICE_ELEMENT_ID>',        // Custom price property (not core)
        '34' => 'jbprice<PRICE_TYPE>__<ELEMENT_ID>',                            // JBPrice only one field/complex data
                                                                                // It may be reapeatable column

        // User fields
        '35' => 'jbimage__<ELEMENT_ID>',                                        // JBImage
        '36' => 'text__<ELEMENT_ID>',                                           // Text
        '37' => 'radio__<ELEMENT_ID>',                                          // Radio
        '38' => '<ELEMENT_TYPE>__<ELEMENT_ID>',                                 // Any element type
    ),
);
