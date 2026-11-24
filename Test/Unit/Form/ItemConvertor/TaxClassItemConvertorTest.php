<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Form\ItemConvertor;

use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use PHPUnit\Framework\TestCase;
use Yireo\TaxClassManager\Form\ItemConvertor\TaxClassItemConvertor;

final class TaxClassItemConvertorTest extends TestCase
{
    public function testClassTypeOfExistingTaxClassIsRestored(): void
    {
        $item = $this->createTaxClassModel(['class_id' => 3, 'class_name' => 'Retail', 'class_type' => 'CUSTOMER']);
        $item->setData('class_name', 'Wholesale');
        $item->setData('class_type', 'PRODUCT');

        $result = (new TaxClassItemConvertor())->beforeSave($item);

        $this->assertSame($item, $result);
        $this->assertSame('CUSTOMER', $result->getData('class_type'));
        $this->assertSame('Wholesale', $result->getData('class_name'));
    }

    public function testClassTypeOfNewTaxClassIsKept(): void
    {
        $item = $this->createTaxClassModel([]);
        $item->setData('class_name', 'New');
        $item->setData('class_type', 'PRODUCT');

        $result = (new TaxClassItemConvertor())->beforeSave($item);

        $this->assertSame('PRODUCT', $result->getData('class_type'));
    }

    public function testPlainDataObjectIsReturnedAsIs(): void
    {
        $item = new DataObject(['class_id' => 3, 'class_type' => 'PRODUCT']);

        $this->assertSame($item, (new TaxClassItemConvertor())->beforeSave($item));
        $this->assertSame($item, (new TaxClassItemConvertor())->afterLoad($item));
        $this->assertSame('PRODUCT', $item->getData('class_type'));
    }

    private function createTaxClassModel(array $origData): AbstractModel
    {
        $model = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $model->setData($origData);
        $model->setOrigData();

        return $model;
    }
}
