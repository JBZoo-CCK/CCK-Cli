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
 * Class ToolsReindex
 * @package JBZoo/Console
 */
class ToolsReindex extends CommandJBZoo
{
    /**
     * Configuration of command
     */
    protected function configure() // @codingStandardsIgnoreLine
    {
        $this
            ->setName('tools:reindex')
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
        $indexModel = \JBModelSearchindex::model();
        $tolalItems = $indexModel->getTotal();
        $indexStep  = $this->_config->find('step', 100);

        $this->_showProfiler('Reindex - prepared');

        $this->_progressBar(
            'Database ReIndex',
            $tolalItems,
            $indexStep,
            function ($offset) use ($indexModel, $indexStep) {
                $reIndex = $indexModel->reIndex($indexStep, $offset);
                return $reIndex < 0 ? false : true;
            }
        );

        $this->_showProfiler('Reindex - Done!');
    }
}
