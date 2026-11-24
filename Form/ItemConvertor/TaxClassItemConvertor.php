<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Form\ItemConvertor;

use Loki\AdminComponents\Form\Item\ItemConvertorInterface;
use Magento\Framework\DataObject;
use Magento\Tax\Model\ClassModelFactory;
use Magento\Tax\Model\ResourceModel\TaxClass as TaxClassResource;

class TaxClassItemConvertor implements ItemConvertorInterface
{
    /**
     * @param ClassModelFactory $classModelFactory
     * @param TaxClassResource $taxClassResource
     */
    public function __construct(
        private readonly ClassModelFactory $classModelFactory,
        private readonly TaxClassResource $taxClassResource
    ) {
    }

    /**
     * @param DataObject $item
     * @return DataObject
     */
    public function afterLoad(DataObject $item): DataObject
    {
        return $item;
    }

    /**
     * @param DataObject $item
     * @return DataObject
     */
    public function beforeSave(DataObject $item): DataObject
    {
        $taxClassId = (int)$item->getData('class_id');
        if ($taxClassId < 1) {
            return $item;
        }

        $storedTaxClass = $this->classModelFactory->create();
        $this->taxClassResource->load($storedTaxClass, $taxClassId);
        if ((int)$storedTaxClass->getData('class_id') !== $taxClassId) {
            return $item;
        }

        $item->setData('class_type', $storedTaxClass->getData('class_type'));

        return $item;
    }
}
