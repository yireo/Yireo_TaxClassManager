<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration\Controller\Adminhtml\Taxclass;

use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Yireo\TaxClassManager\Test\Integration\FixtureTrait\CreateTaxClass;

#[DbIsolation(true)]
final class GridTest extends AbstractBackendController
{
    use CreateTaxClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = 'Yireo_TaxClassManager::tax_class_view';
        $this->uri = 'backend/tax_class_manager/taxclass/grid';
    }

    public function testGridPageRenders(): void
    {
        $this->dispatch($this->uri);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());

        $body = (string)$this->getResponse()->getBody();
        $this->assertStringContainsString('Tax Classes', $body);
        $this->assertStringContainsString('data-role="grid"', $body);
        $this->assertStringContainsString('Add New Tax Class', $body);
        $this->assertStringContainsString('tax_class_manager/taxclass/massDelete', $body);
    }

    public function testGridListsTaxClassesWithTypeLabel(): void
    {
        $customerClass = $this->createTaxClass(TaxClassManagementInterface::TYPE_CUSTOMER);
        $productClass = $this->createTaxClass(TaxClassManagementInterface::TYPE_PRODUCT);

        $this->dispatch($this->uri);
        $body = (string)$this->getResponse()->getBody();

        $customerRow = $this->getRowHtml($body, $customerClass->getClassName());
        $this->assertStringContainsString('<span>Customer</span>', $customerRow);
        $this->assertStringContainsString('data-row-id="' . $customerClass->getClassId() . '"', $customerRow);
        $this->assertStringContainsString(
            'tax_class_manager/taxclass/form/id/' . $customerClass->getClassId() . '/',
            $customerRow
        );
        $this->assertStringContainsString(
            'tax_class_manager/taxclass/delete/id/' . $customerClass->getClassId() . '/',
            $customerRow
        );

        $productRow = $this->getRowHtml($body, $productClass->getClassName());
        $this->assertStringContainsString('<span>Product</span>', $productRow);
    }

    private function getRowHtml(string $body, string $className): string
    {
        preg_match_all('#<tr class="data-row[^"]*">(.*?)</tr>#s', $body, $matches);
        foreach ($matches[1] as $row) {
            if (str_contains($row, $className)) {
                return $row;
            }
        }

        $this->fail('No grid row found for tax class "' . $className . '"');
    }
}
