<?php

namespace PixelPerfect\CmsImporter\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoreHelper
{

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface  $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig  = $scopeConfig;
    }

    /**
     * Filter the Stores by an Array of allowed items
     *
     * @param array $allowedLocales
     *
     * @return array
     */
    public function filterStoresByLocale(array $allowedLocales)
    {
        $localeStores = $this->getLocaleForAllStores();
        return array_filter(
            $localeStores,
            function ($key) use ($allowedLocales) {
                return in_array($key, $allowedLocales);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Get list of Locales/StoreCodes
     *
     * @return array
     */
    public function getLocaleForAllStores()
    {
        $localeStores = [];
        /** @var StoreInterface[] $stores */
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $localeCode                = $this->scopeConfig->getValue(
                'general/locale/code', ScopeInterface::SCOPE_STORE, $store->getId()
            );
            $localeStores[$localeCode] = $store->getId();
        }
        return $localeStores;
    }
}
