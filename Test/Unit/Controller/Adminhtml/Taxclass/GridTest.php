<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Controller\Adminhtml\Taxclass;

use Yireo\TaxClassManager\Controller\Adminhtml\Taxclass\Grid;

final class GridTest extends AbstractControllerTestCase
{
    use PageResultTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPageResult();
    }

    public function testAdminResource(): void
    {
        $this->assertSame('Yireo_TaxClassManager::tax_class_view', Grid::ADMIN_RESOURCE);
    }

    public function testExecuteReturnsConfiguredPage(): void
    {
        $result = (new Grid($this->context, $this->pageFactory))->execute();

        $this->assertSame($this->page, $result);
        $this->assertSame(['Yireo_TaxClassManager::tax_class'], $this->activeMenus);
        $this->assertSame([['Taxes', 'Taxes'], ['Tax Classes', 'Tax Classes']], $this->breadcrumbs);
        $this->assertSame(['Tax Classes'], $this->titles);
        $this->assertSame([], $this->handles);
        $this->assertSame([], $this->redirectPaths);
    }
}
