<?php

/*
 * This file is part of the Yosymfony\Spress.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yosymfony\Spress\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yosymfony\Spress\IO\ConsoleIO;
use Yosymfony\Spress\Scaffolding\SiteGenerator;

/**
 * New site command.
 *
 * @author Victor Puertas <vpgugr@gmail.com>
 */
class NewSiteCommand extends BaseCommand
{
    /** @var string */
    const BLANK_THEME = 'blank';

    /** @var string */
    const SPRESSO_THEME = 'spresso';

    /** @var string */
    const SPRESS_INSTALLER_PACKAGE = 'spress/spress-installer >= 2.1';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDefinition([
            new InputArgument('path', InputArgument::OPTIONAL, 'Path of the new site', './'),
            new InputArgument('template', InputArgument::OPTIONAL, 'Package name', self::BLANK_THEME),
            new InputOption('force', '', InputOption::VALUE_NONE, 'Force creation even if path already exists'),
            new InputOption('all', '', InputOption::VALUE_NONE, 'Complete scaffold'),
        ])
        ->setName('new:site')
        ->setDescription('Create a new site')
        ->setHelp('The <info>new:site</info> command helps you generates new sites.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $template = $input->getArgument('template');
        $force = $input->getOption('force');
        $completeScaffold = $input->getOption('all');
        $io = new ConsoleIO($input, $output);

        if ($template === self::SPRESSO_THEME) {
            $template = 'spress/spress-theme-spresso';
        }

        $this->startingMessage($io, $template);

        $generator = new SiteGenerator($this->getPackageManager($path, $io), self::SPRESS_INSTALLER_PACKAGE);
        $generator->setSkeletonDirs([$this->getSkeletonsDir()]);
        $generator->generate($path, $template, $force);

        $io->success(sprintf('New site with theme "%s" created at "%s" folder', $template, $path));
    }

    /**
     * Writes the staring messages.
     *
     * @param ConsoleIO $io       Spress IO
     * @param string    $template The template
     */
    protected function startingMessage(ConsoleIO $io, $template)
    {
        $io->newLine();
        $io->write(sprintf('<comment>Generating a site using the theme: "%s"...</comment>', $template));
    }
}
