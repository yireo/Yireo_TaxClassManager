<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration\Controller\Adminhtml\Taxclass;

use Loki\AdminComponents\Acl\ActionAuthorization;
use Loki\AdminComponents\Grid\BookmarkLoader;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\View\LayoutInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Yireo\TaxClassManager\Test\Integration\FixtureTrait\CreateTaxClass;

#[DbIsolation(true)]
#[AppIsolation(true)]
#[AppArea('adminhtml')]
final class GridTest extends AbstractBackendController
{
    use CreateTaxClass;

    private const NAMESPACE = 'yireo_tax_class_manager_listing';

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

    public function testRowDeleteActionPostsWithConfirmation(): void
    {
        $taxClass = $this->createTaxClass();

        $this->dispatch($this->uri);
        $row = $this->getRowHtml((string)$this->getResponse()->getBody(), $taxClass->getClassName());

        $this->assertMatchesRegularExpression(
            '#<a\s+href="\#"\s+data-url="[^"]+tax_class_manager/taxclass/delete/id/' . $taxClass->getClassId()
            . '/[^"]*"\s+data-confirm="Are you sure you want to delete this tax class\?"\s+'
            . '@click.prevent="postAction">#',
            $row
        );
        $this->assertStringNotContainsString('<a href="http', $this->getDeleteLinkHtml($row));
    }

    public function testDeleteActionsAreHiddenWithoutDeletePermission(): void
    {
        $taxClass = $this->createTaxClass();
        $this->denyResource('Yireo_TaxClassManager::tax_class_delete');

        $this->dispatch($this->uri);
        $body = (string)$this->getResponse()->getBody();

        $this->assertStringNotContainsString('tax_class_manager/taxclass/massDelete', $body);
        $this->assertStringNotContainsString(
            'tax_class_manager/taxclass/delete',
            $this->getRowHtml($body, $taxClass->getClassName())
        );
        $this->assertFalse($this->isGridActionAllowed('delete'));
        $this->assertTrue($this->isGridActionAllowed('inline_edit'));
    }

    public function testSaveActionsAreRefusedWithoutSavePermission(): void
    {
        $this->denyResource('Yireo_TaxClassManager::tax_class_save');

        $this->dispatch($this->uri);

        $this->assertStringNotContainsString('Add New Tax Class', (string)$this->getResponse()->getBody());
        $this->assertFalse($this->isGridActionAllowed('inline_edit'));
        $this->assertTrue($this->isGridActionAllowed('delete'));
    }

    private function isGridActionAllowed(string $action): bool
    {
        $block = $this->_objectManager->get(LayoutInterface::class)->getBlock('yireo-tax-class-manager.grid');
        $this->assertNotFalse($block, 'Grid block not found in layout');

        return $this->_objectManager->get(ActionAuthorization::class)->isAllowed($block, $action);
    }

    private function denyResource(string $resource): void
    {
        $this->_objectManager->get(AclBuilder::class)->getAcl()->deny($this->_auth->getUser()->getRoles(), $resource);
    }

    private function getDeleteLinkHtml(string $row): string
    {
        preg_match('#<a[^>]*>\s*Delete\s*</a>#s', $row, $match);
        $this->assertNotEmpty($match, 'No delete link found');

        return $match[0];
    }

    /**
     * The grid stores its columns in a bookmark when it is rendered for the first time, so every later visit has one
     */
    public function testColumnsAreRenderedOnceWhenBookmarkExists(): void
    {
        $taxClass = $this->createTaxClass(TaxClassManagementInterface::TYPE_CUSTOMER);
        $this->createBookmark(['class_id' => 1, 'class_name' => 1, 'class_type' => 1]);

        $this->dispatch($this->uri);
        $body = (string)$this->getResponse()->getBody();

        $this->assertSame(['class_id', 'class_name', 'class_type'], $this->getHeaderColumns($body));

        $row = $this->getRowHtml($body, $taxClass->getClassName());
        $this->assertSame(5, substr_count($row, '<td '), 'Expected a checkbox cell, 3 data cells and an actions cell');
        $this->assertSame(1, substr_count($row, '<span>Customer</span>'));
    }

    public function testColumnHiddenInBookmarkIsNotRendered(): void
    {
        $taxClass = $this->createTaxClass(TaxClassManagementInterface::TYPE_CUSTOMER);
        $this->createBookmark(['class_id' => 1, 'class_name' => 1, 'class_type' => 0]);

        $this->dispatch($this->uri);
        $body = (string)$this->getResponse()->getBody();

        $this->assertSame(['class_id', 'class_name'], $this->getHeaderColumns($body));

        $row = $this->getRowHtml($body, $taxClass->getClassName());
        $this->assertSame(4, substr_count($row, '<td '), 'Expected a checkbox cell, 2 data cells and an actions cell');
        $this->assertStringNotContainsString('<span>Customer</span>', $row);
    }

    /**
     * @param array<string, int> $visibleColumns
     */
    private function createBookmark(array $visibleColumns): void
    {
        $columns = [];
        foreach ($visibleColumns as $columnName => $visible) {
            $columns[$columnName] = ['visible' => $visible];
        }

        $this->_objectManager->get(BookmarkLoader::class)->createBookmark(self::NAMESPACE, ['columns' => $columns]);
    }

    /**
     * @return string[]
     */
    private function getHeaderColumns(string $body): array
    {
        preg_match_all('#<th[^>]+data-column="([^"]+)"#', $body, $matches);

        return $matches[1];
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
