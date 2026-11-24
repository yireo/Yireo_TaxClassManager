<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration\FixtureTrait;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\Data\TaxClassInterfaceFactory;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

trait CreateTaxClass
{
    private function createTaxClass(
        string $classType = TaxClassManagementInterface::TYPE_CUSTOMER,
        ?string $className = null
    ): TaxClassInterface {
        /** @var TaxClassInterface $taxClass */
        $taxClass = Bootstrap::getObjectManager()->get(TaxClassInterfaceFactory::class)->create();
        $taxClass->setClassName($className ?? 'Integration Tax Class ' . uniqid());
        $taxClass->setClassType($classType);

        $taxClassId = $this->getTaxClassRepository()->save($taxClass);

        return $this->getTaxClassRepository()->get($taxClassId);
    }

    private function taxClassExists(int $taxClassId): bool
    {
        try {
            $this->getTaxClassRepository()->get($taxClassId);
            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Return the ID of a tax class that Magento refuses to delete, because a customer group uses it; another tax
     * class becomes the default customer tax class, so that only the Magento check applies
     */
    private function getTaxClassIdInUse(): int
    {
        $objectManager = Bootstrap::getObjectManager();
        $customerGroup = $objectManager->get(GroupRepositoryInterface::class)->getById(1);

        $configPath = 'tax/classes/default_customer_tax_class';
        $otherTaxClassId = (int)$this->createTaxClass()->getClassId();
        $this->setDefaultTaxClass($configPath, $otherTaxClassId);

        $storeManager = $objectManager->get(StoreManagerInterface::class);
        foreach ($storeManager->getWebsites() as $website) {
            $this->setDefaultTaxClass($configPath, $otherTaxClassId, ScopeInterface::SCOPE_WEBSITES, $website->getCode());
        }

        foreach ($storeManager->getStores() as $store) {
            $this->setDefaultTaxClass($configPath, $otherTaxClassId, ScopeInterface::SCOPE_STORES, $store->getCode());
        }

        return (int)$customerGroup->getTaxClassId();
    }

    private function setDefaultTaxClass(
        string $configPath,
        int $taxClassId,
        string $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        ?string $scopeCode = null
    ): void {
        Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class)
            ->setValue($configPath, (string)$taxClassId, $scopeType, $scopeCode);
    }

    private function getTaxClassRepository(): TaxClassRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(TaxClassRepositoryInterface::class);
    }
}
