<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Controller\Adminhtml\Taxclass;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Yireo\TaxClassManager\Controller\Adminhtml\Taxclass\Form;

final class FormTest extends AbstractControllerTestCase
{
    use PageResultTrait;

    private TaxClassRepositoryInterface&MockObject $taxClassRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPageResult();
        $this->taxClassRepository = $this->createMock(TaxClassRepositoryInterface::class);
    }

    public function testAdminResource(): void
    {
        $this->assertSame('Yireo_TaxClassManager::tax_class_save', Form::ADMIN_RESOURCE);
    }

    #[DataProvider('newTaxClassIdProvider')]
    public function testNewTaxClassRendersNewPage(mixed $id): void
    {
        $this->requestParams = ['id' => $id];
        $this->taxClassRepository->expects($this->never())->method('get');

        $result = $this->createController()->execute();

        $this->assertSame($this->page, $result);
        $this->assertSame([], $this->handles);
        $this->assertSame(['New Tax Class'], $this->titles);
        $this->assertSame(
            [['Taxes', 'Taxes'], ['Tax Classes', 'Tax Classes'], ['New Tax Class', 'New Tax Class']],
            $this->breadcrumbs
        );
        $this->assertSame(['Yireo_TaxClassManager::tax_class'], $this->activeMenus);
        $this->assertSame([], $this->errorMessages);
        $this->assertSame([], $this->redirectPaths);
    }

    public static function newTaxClassIdProvider(): array
    {
        return [
            'missing' => [null],
            'zero' => ['0'],
            'negative' => ['-1'],
        ];
    }

    public function testExistingTaxClassRendersEditPage(): void
    {
        $this->requestParams = ['id' => '3'];
        $this->taxClassRepository->expects($this->once())->method('get')->with(3)
            ->willReturn($this->createMock(TaxClassInterface::class));

        $result = $this->createController()->execute();

        $this->assertSame($this->page, $result);
        $this->assertSame(['yireo_taxclassmanager_taxclass_form_edit'], $this->handles);
        $this->assertSame(['Edit Tax Class'], $this->titles);
        $this->assertSame(
            [['Taxes', 'Taxes'], ['Tax Classes', 'Tax Classes'], ['Edit Tax Class', 'Edit Tax Class']],
            $this->breadcrumbs
        );
        $this->assertSame(['Yireo_TaxClassManager::tax_class'], $this->activeMenus);
        $this->assertSame([], $this->errorMessages);
        $this->assertSame([], $this->redirectPaths);
    }

    public function testMissingTaxClassRedirectsToGrid(): void
    {
        $this->requestParams = ['id' => '999'];
        $this->taxClassRepository->method('get')->willThrowException(new NoSuchEntityException());
        $this->pageFactory->expects($this->never())->method('create');

        $result = $this->createController()->execute();

        $this->assertSame($this->redirect, $result);
        $this->assertSame(['This tax class no longer exists.'], $this->errorMessages);
        $this->assertRedirectedToGrid();
        $this->assertSame([], $this->titles);
    }

    private function createController(): Form
    {
        return new Form($this->context, $this->pageFactory, $this->taxClassRepository);
    }
}
