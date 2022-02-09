<?php
declare(strict_types=1);
/**
 * Copyright © André Flitsch. All rights reserved.
 * See license.md for license details.
 */

namespace PixelPerfect\CmsImporter\Helper;

use Iterator;
use League\Csv\Exception;
use League\Csv\Modifier\MapIterator;
use League\Csv\Reader as CsvReader;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Dir\Reader as DirectoryReader;

/**
 * Class CmsHelper
 *
 * @package PixelPerfect\CmsImporter\Helper
 */
class CmsHelper
{

    const DIR_VAR       = 'var';
    const CSV_DELIMITER = ';';
    const CSV_ENCLOSURE = '\'';

    /**
     * @var DirectoryReader
     */
    private $directoryReader;

    /**
     * @var BlockFactory
     */
    private $blockRepository;

    /**
     * @var BlockFactory
     */
    private $blockFactory;

    /**
     * @var StoreHelper
     */
    private $storeHelper;

    /**
     * @var string|null
     */
    private $fileName = null;

    /**
     * @var string|null
     */
    private $blockPrefix = null;

    /**
     * @var string|null
     */
    private $pagePrefix = null;

    /**
     * @var array
     */
    private $allowedLocales = [];

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var GetPageByIdentifierInterface
     */
    private $getPageByIdentifier;

    /**
     * @var GetBlockByIdentifierInterface
     */
    private $blockByIdentifier;

    /**
     * @var bool
     */
    private $useModuleDirectory = true;

    /**
     * CmsHelper constructor.
     *
     * @param DirectoryReader               $directoryReader
     * @param GetBlockByIdentifierInterface $blockByIdentifier
     * @param BlockRepositoryInterface      $blockRepository
     * @param BlockFactory                  $blockFactory
     * @param GetPageByIdentifierInterface  $getPageByIdentifier
     * @param PageRepositoryInterface       $pageRepository
     * @param PageFactory                   $pageFactory
     * @param StoreHelper                   $storeHelper
     */
    public function __construct(
        DirectoryReader               $directoryReader,
        GetBlockByIdentifierInterface $blockByIdentifier,
        BlockRepositoryInterface      $blockRepository,
        BlockFactory                  $blockFactory,
        GetPageByIdentifierInterface  $getPageByIdentifier,
        PageRepositoryInterface       $pageRepository,
        PageFactory                   $pageFactory,
        StoreHelper                   $storeHelper
    ) {
        $this->directoryReader     = $directoryReader;
        $this->blockRepository     = $blockRepository;
        $this->blockFactory        = $blockFactory;
        $this->storeHelper         = $storeHelper;
        $this->pageFactory         = $pageFactory;
        $this->pageRepository      = $pageRepository;
        $this->getPageByIdentifier = $getPageByIdentifier;
        $this->blockByIdentifier   = $blockByIdentifier;
    }

    /**
     * Create CMS Blocks from CSV records
     *
     * @param MapIterator $csvRecords
     *
     * @throws LocalizedException
     */
    public function createCmsBlocks(MapIterator $csvRecords)
    {
        $localeStores = $this->fetchLocaleStores();

        foreach ($csvRecords as $csvRecord) {
            $blockId = strtolower($this->getBlockPrefix() . $csvRecord['identifier']);

            foreach ($localeStores as $locale => $storeId) {
                if ($csvRecord['locale'] === $locale) {
                    $block = $this->blockFactory->create();
                    $block->setIdentifier($blockId)
                        ->setTitle($csvRecord['title'])
                        ->setContent($csvRecord['content'])
                        ->setStores([$storeId]);
                    $this->blockRepository->save($block);
                    unset($block);
                }
            }
        }
    }

    public function createOrUpdateCmsPages(MapIterator $csvRecords)
    {
        $localeStores = $this->fetchLocaleStores();

        foreach ($csvRecords as $csvRecord) {
            $identifier = strtolower($this->getPagePrefix() . $csvRecord['identifier']);

            foreach ($localeStores as $locale => $storeId) {

                try {
                    $page = $this->getPageByIdentifier->execute($identifier, (int)$storeId);
                } catch (NoSuchEntityException $exception) {
                    $page = $this->pageFactory->create();
                    $page->setIdentifier($identifier);
                }

                $page->setTitle($csvRecord['title-' . $locale])
                    ->setData('secondary_title', $csvRecord['secondary_title-' . $locale])
                    ->setMetaKeywords($csvRecord['meta_keywords-' . $locale])
                    ->setMetaDescription($csvRecord['meta_description-' . $locale])
                    ->setMetaTitle($csvRecord['meta_title-' . $locale])
                    ->setContent($csvRecord['content-' . $locale])
                    ->setPageLayout($csvRecord['page_layout'])
                    ->setContentHeading($csvRecord['content_heading'])
                    ->setLayoutUpdateXml($csvRecord['layout_update_xml'])
                    ->setStores([$storeId]);

                $this->pageRepository->save($page);
                unset($page);
            }
        }
    }

    /**
     * Fetch CSV records from file
     *
     * @param callable|null $func
     *
     * @return Iterator
     * @throws Exception
     */
    public function fetchCsvRecords(callable $func = null): Iterator
    {
        if ($this->isUseModuleDirectory()) {
            $moduleDirectory = $this->directoryReader->getModuleDir('', 'PixelPerfect_CmsImporter');
            $filePath        = $moduleDirectory . DIRECTORY_SEPARATOR . self::DIR_VAR . DIRECTORY_SEPARATOR
                . $this->getFileName();
        } else {
            $filePath = $this->getFileName();
        }

        return CsvReader::createFromPath($filePath)
            ->setDelimiter(self::CSV_DELIMITER)
            ->setEnclosure(self::CSV_ENCLOSURE)
            ->fetchAssoc(0, $func);
    }

    /**
     *
     * @return array
     */
    public function getAllowedLocales(): array
    {
        return $this->allowedLocales;
    }

    /**
     *
     * @param array $allowedLocales
     */
    public function setAllowedLocales(array $allowedLocales): void
    {
        $this->allowedLocales = $allowedLocales;
    }

    /**
     * @return GetBlockByIdentifierInterface
     */
    public function getBlockByIdentifier(): GetBlockByIdentifierInterface
    {
        return $this->blockByIdentifier;
    }

    /**
     * @param GetBlockByIdentifierInterface $blockByIdentifier
     */
    public function setBlockByIdentifier(GetBlockByIdentifierInterface $blockByIdentifier): void
    {
        $this->blockByIdentifier = $blockByIdentifier;
    }

    /**
     *
     * @return string|null
     */
    public function getBlockPrefix(): ?string
    {
        return $this->blockPrefix;
    }

    /**
     *
     * @param string|null $blockPrefix
     */
    public function setBlockPrefix(?string $blockPrefix): void
    {
        $this->blockPrefix = $blockPrefix;
    }

    /**
     *
     * @return string|null
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     *
     * @param string|null $fileName
     */
    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string|null
     */
    public function getPagePrefix(): ?string
    {
        return $this->pagePrefix;
    }

    /**
     * @param string|null $pagePrefix
     */
    public function setPagePrefix(?string $pagePrefix): void
    {
        $this->pagePrefix = $pagePrefix;
    }

    /**
     * @return bool
     */
    public function isUseModuleDirectory(): bool
    {
        return $this->useModuleDirectory;
    }

    /**
     * @param bool $useModuleDirectory
     */
    public function setUseModuleDirectory(bool $useModuleDirectory): void
    {
        $this->useModuleDirectory = $useModuleDirectory;
    }

    /**
     * @return array
     */
    private function fetchLocaleStores(): array
    {
        $localeStores = $this->storeHelper->getLocaleForAllStores();

        $allowedLocales = $this->getAllowedLocales();
        if (count($allowedLocales) > 0) {
            $localeStores = $this->storeHelper->filterStoresByLocale($allowedLocales);
        }
        return $localeStores;
    }
}
