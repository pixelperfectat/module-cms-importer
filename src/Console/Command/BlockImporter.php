<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace PixelPerfect\CmsImporter\Console\Command;

use Exception;
use Magento\Framework\Console\Cli;
use PixelPerfect\CmsImporter\Helper\CmsHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BlockImporter extends Command
{

    const FILENAME = 'filename';

    /**
     * @var CmsHelper
     */
    private $cmsHelper;

    public function __construct(
        CmsHelper $cmsHelper,
        string    $name = null
    ) {

        parent::__construct($name);
        $this->cmsHelper = $cmsHelper;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('cms-importer:block');
        $this->setDescription('Import CMS blocks from specified source file');
        $this->addArgument(self::FILENAME, InputArgument::REQUIRED,'Full path to the file being imported');
        parent::configure();
    }

    /**
     * CLI command description
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument(self::FILENAME);

        try {
            $this->cmsHelper->setUseModuleDirectory(false);
            $this->cmsHelper->setFileName($filename);
            $this->cmsHelper->setAllowedLocales(['de_DE']);
            $csvRecords = $this->cmsHelper->fetchCsvRecords();
            $this->cmsHelper->createCmsBlocks($csvRecords);
        } catch (Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Cli::RETURN_FAILURE;
        }
        return Cli::RETURN_SUCCESS;
    }
}
