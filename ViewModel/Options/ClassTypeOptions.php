<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\ViewModel\Options;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Tax\Api\TaxClassManagementInterface;

class ClassTypeOptions implements OptionSourceInterface, ArgumentInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => TaxClassManagementInterface::TYPE_CUSTOMER,
                'label' => __('Customer'),
            ],
            [
                'value' => TaxClassManagementInterface::TYPE_PRODUCT,
                'label' => __('Product'),
            ],
        ];
    }

    /**
     * @param string $classType
     * @return string
     */
    public function getLabel(string $classType): string
    {
        foreach ($this->toOptionArray() as $option) {
            if ($option['value'] === $classType) {
                return (string)$option['label'];
            }
        }

        return $classType;
    }
}
