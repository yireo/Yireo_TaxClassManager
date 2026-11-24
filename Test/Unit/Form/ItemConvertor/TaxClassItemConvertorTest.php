<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Form\ItemConvertor;

use Magento\Framework\DataObject;
use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\ClassModelFactory;
use Magento\Tax\Model\ResourceModel\TaxClass as TaxClassResource;
use PHPUnit\Framework\TestCase;
use Yireo\TaxClassManager\Form\ItemConvertor\TaxClassItemConvertor;

final class TaxClassItemConvertorTest extends TestCase
{
    public function testClassTypeOfExistingTaxClassIsRestored(): void
    {
        $item = new DataObject(['class_id' => 3, 'class_name' => 'Wholesale', 'class_type' => 'PRODUCT']);

        $result = $this->createConvertor(['class_id' => 3, 'class_type' => 'CUSTOMER'])->beforeSave($item);

        $this->assertSame($item, $result);
        $this->assertSame('CUSTOMER', $result->getData('class_type'));
        $this->assertSame('Wholesale', $result->getData('class_name'));
    }

    public function testClassTypeOfNewTaxClassIsKept(): void
    {
        $item = new DataObject(['class_name' => 'New', 'class_type' => 'PRODUCT']);

        $result = $this->createConvertor(null)->beforeSave($item);

        $this->assertSame('PRODUCT', $result->getData('class_type'));
    }

    public function testClassTypeIsKeptWhenStoredTaxClassIsMissing(): void
    {
        $item = new DataObject(['class_id' => 99, 'class_type' => 'PRODUCT']);

        $result = $this->createConvertor([])->beforeSave($item);

        $this->assertSame('PRODUCT', $result->getData('class_type'));
    }

    public function testAfterLoadReturnsItem(): void
    {
        $item = new DataObject(['class_id' => 3]);

        $this->assertSame($item, $this->createConvertor(null)->afterLoad($item));
    }

    private function createConvertor(?array $storedData): TaxClassItemConvertor
    {
        $storedTaxClass = $this->getMockBuilder(ClassModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $classModelFactory = $this->createMock(ClassModelFactory::class);
        $classModelFactory->expects($storedData === null ? $this->never() : $this->once())
            ->method('create')
            ->willReturn($storedTaxClass);

        $taxClassResource = $this->createStub(TaxClassResource::class);
        $taxClassResource->method('load')->willReturnCallback(
            function (ClassModel $object) use ($storedData, $taxClassResource) {
                $object->setData((array)$storedData);
                return $taxClassResource;
            }
        );

        return new TaxClassItemConvertor($classModelFactory, $taxClassResource);
    }
}
