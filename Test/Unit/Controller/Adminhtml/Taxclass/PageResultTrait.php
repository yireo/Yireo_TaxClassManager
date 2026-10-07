<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Controller\Adminhtml\Taxclass;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;

trait PageResultTrait
{
    protected Page&MockObject $page;
    protected PageFactory&MockObject $pageFactory;
    protected array $activeMenus = [];
    protected array $breadcrumbs = [];
    protected array $handles = [];
    protected array $titles = [];

    protected function setUpPageResult(): void
    {
        $title = $this->createMock(Title::class);
        $title->method('prepend')->willReturnCallback(
            function ($value) {
                $this->titles[] = (string)$value;
            }
        );

        $pageConfig = $this->createMock(Config::class);
        $pageConfig->method('getTitle')->willReturn($title);

        $this->page = $this->createMock(Page::class);
        $this->page->method('getConfig')->willReturn($pageConfig);
        $this->page->method('setActiveMenu')->willReturnCallback(
            function (string $menu) {
                $this->activeMenus[] = $menu;
                return $this->page;
            }
        );
        $this->page->method('addBreadcrumb')->willReturnCallback(
            function ($label, $title) {
                $this->breadcrumbs[] = [(string)$label, (string)$title];
                return $this->page;
            }
        );
        $this->page->method('addHandle')->willReturnCallback(
            function ($handle) {
                $this->handles[] = $handle;
                return $this->page;
            }
        );

        $this->pageFactory = $this->createMock(PageFactory::class);
        $this->pageFactory->method('create')->willReturn($this->page);
    }
}
