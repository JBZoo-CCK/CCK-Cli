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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ToolReindex
 * @package JBZoo/Console
 */
class ToolReindex extends CommandJBZoo
{
    /**
     * Configuration of command
     */
    protected function configure() // @codingStandardsIgnoreLine
    {
        $this
            ->setName('tool:reindex')
            ->setDescription('Reindex database for JBZoo filter');
    }

    /**
     * Execute method of command
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine
    {
        $this->_executePrepare($input, $output);
        $this->_init();

        // init vars
        $offset     = 0;
        $reIndex    = -1;
        $indexModel = \JBModelSearchindex::model();
        $tolalItems = $indexModel->getTotal();
        $indexStep  = $this->_config->find('step', 100);

        $progress = new ProgressBar($output, $tolalItems);
        $progress->advance(0);

        while ($reIndex != 0) {
            $reIndex = $indexModel->reIndex($indexStep, $offset);
            $offset += $indexStep;
            $progress->advance($indexStep);
        }

        $progress->finish();
    }
}
