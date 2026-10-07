<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\ViewModel\Options;

use Magento\Tax\Api\TaxClassManagementInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\TaxClassManager\ViewModel\Options\ClassTypeOptions;

final class ClassTypeOptionsTest extends TestCase
{
    public function testToOptionArrayReturnsCustomerAndProductOptions(): void
    {
        $options = (new ClassTypeOptions())->toOptionArray();

        $this->assertCount(2, $options);
        $this->assertSame(TaxClassManagementInterface::TYPE_CUSTOMER, $options[0]['value']);
        $this->assertSame('Customer', (string)$options[0]['label']);
        $this->assertSame(TaxClassManagementInterface::TYPE_PRODUCT, $options[1]['value']);
        $this->assertSame('Product', (string)$options[1]['label']);
    }

    public function testToOptionArrayEntriesOnlyContainValueAndLabel(): void
    {
        foreach ((new ClassTypeOptions())->toOptionArray() as $option) {
            $this->assertSame(['value', 'label'], array_keys($option));
        }
    }

    public function testGetLabelReturnsCustomerLabel(): void
    {
        $label = (new ClassTypeOptions())->getLabel(TaxClassManagementInterface::TYPE_CUSTOMER);

        $this->assertSame('Customer', $label);
    }

    public function testGetLabelReturnsProductLabel(): void
    {
        $label = (new ClassTypeOptions())->getLabel(TaxClassManagementInterface::TYPE_PRODUCT);

        $this->assertSame('Product', $label);
    }

    #[DataProvider('unknownClassTypeProvider')]
    public function testGetLabelReturnsInputForUnknownClassType(string $classType): void
    {
        $this->assertSame($classType, (new ClassTypeOptions())->getLabel($classType));
    }

    public static function unknownClassTypeProvider(): array
    {
        return [
            'unknown type' => ['SHIPPING'],
            'empty string' => [''],
            'lowercase type' => ['customer'],
        ];
    }
}
