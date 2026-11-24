<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Form\ItemConvertor;

use Loki\AdminComponents\Form\Item\ItemConvertorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;

class TaxClassItemConvertor implements ItemConvertorInterface
{
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
        if (false === $item instanceof AbstractModel || empty($item->getOrigData('class_id'))) {
            return $item;
        }

        $item->setData('class_type', $item->getOrigData('class_type'));

        return $item;
    }
}
