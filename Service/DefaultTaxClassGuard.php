<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class DefaultTaxClassGuard
{
    private const CONFIG_PATHS = [
        'tax/classes/default_product_tax_class',
        'tax/classes/default_customer_tax_class',
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param int $taxClassId
     * @return void
     * @throws LocalizedException
     */
    public function assertCanBeDeleted(int $taxClassId): void
    {
        foreach ($this->getScopes() as [$scopeType, $scopeCode]) {
            foreach (self::CONFIG_PATHS as $configPath) {
                $value = $this->scopeConfig->getValue($configPath, $scopeType, $scopeCode);
                if (is_numeric($value) && (int)$value === $taxClassId) {
                    throw new LocalizedException(
                        __(
                            'You cannot delete this tax class because it is used in the tax configuration'
                            . ' as a default tax class. Choose another default tax class first.'
                        )
                    );
                }
            }
        }
    }

    /**
     * @return array<int, array{0: string, 1: int|string|null}>
     */
    private function getScopes(): array
    {
        $scopes = [[ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null]];

        foreach ($this->storeManager->getWebsites() as $website) {
            $scopes[] = [ScopeInterface::SCOPE_WEBSITES, $website->getId()];
        }

        foreach ($this->storeManager->getStores() as $store) {
            $scopes[] = [ScopeInterface::SCOPE_STORES, $store->getId()];
        }

        return $scopes;
    }
}
