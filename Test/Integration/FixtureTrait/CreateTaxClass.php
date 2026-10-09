<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration\FixtureTrait;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * Return the ID of a tax class that Magento refuses to delete, because a customer group uses it
     */
    private function getTaxClassIdInUse(): int
    {
        $customerGroup = Bootstrap::getObjectManager()->get(GroupRepositoryInterface::class)->getById(1);

        return (int)$customerGroup->getTaxClassId();
    }

    private function getTaxClassRepository(): TaxClassRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(TaxClassRepositoryInterface::class);
    }
}
