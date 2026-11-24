<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Plugin;

use Loki\AdminComponents\Component\Form\FormRepository;
use Loki\AdminComponents\Exception\FormActionException;
use Loki\AdminComponents\Form\Action\DeleteAction;
use Loki\Components\Component\ComponentInterface;
use Loki\Components\Component\ComponentViewModelInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\AbstractBlock;
use PHPUnit\Framework\TestCase;
use Yireo\TaxClassManager\Plugin\ProtectDefaultTaxClassPlugin;
use Yireo\TaxClassManager\Service\DefaultTaxClassGuard;

final class ProtectDefaultTaxClassPluginTest extends TestCase
{
    public function testDefaultTaxClassIsNotDeleted(): void
    {
        $guard = $this->createMock(DefaultTaxClassGuard::class);
        $guard->expects($this->once())->method('assertCanBeDeleted')->with(2)
            ->willThrowException(new LocalizedException(new Phrase('Used as default.')));

        $messageManager = $this->createMock(ManagerInterface::class);
        $messageManager->expects($this->once())->method('addErrorMessage')->with('Used as default.');

        $this->expectException(FormActionException::class);

        (new ProtectDefaultTaxClassPlugin($guard, $messageManager))->beforeExecute(
            $this->createStub(DeleteAction::class),
            $this->createFormRepository(ProtectDefaultTaxClassPlugin::FORM_BLOCK_NAME),
            ['item' => ['class_id' => '2']]
        );
    }

    public function testOtherTaxClassIsDeleted(): void
    {
        $guard = $this->createMock(DefaultTaxClassGuard::class);
        $guard->expects($this->once())->method('assertCanBeDeleted')->with(7);

        $result = (new ProtectDefaultTaxClassPlugin($guard, $this->createStub(ManagerInterface::class)))
            ->beforeExecute(
                $this->createStub(DeleteAction::class),
                $this->createFormRepository(ProtectDefaultTaxClassPlugin::FORM_BLOCK_NAME),
                ['item' => ['class_id' => 7]]
            );

        $this->assertNull($result);
    }

    public function testOtherFormsAreIgnored(): void
    {
        $guard = $this->createMock(DefaultTaxClassGuard::class);
        $guard->expects($this->never())->method('assertCanBeDeleted');

        $result = (new ProtectDefaultTaxClassPlugin($guard, $this->createStub(ManagerInterface::class)))
            ->beforeExecute(
                $this->createStub(DeleteAction::class),
                $this->createFormRepository('vendor-module.form'),
                ['item' => ['class_id' => 2]]
            );

        $this->assertNull($result);
    }

    public function testMissingIdIsIgnored(): void
    {
        $guard = $this->createMock(DefaultTaxClassGuard::class);
        $guard->expects($this->never())->method('assertCanBeDeleted');

        (new ProtectDefaultTaxClassPlugin($guard, $this->createStub(ManagerInterface::class)))->beforeExecute(
            $this->createStub(DeleteAction::class),
            $this->createFormRepository(ProtectDefaultTaxClassPlugin::FORM_BLOCK_NAME),
            ['item' => []]
        );
    }

    private function createFormRepository(string $blockName): FormRepository
    {
        $block = $this->createStub(AbstractBlock::class);
        $block->method('getNameInLayout')->willReturn($blockName);

        $viewModel = $this->createStub(ComponentViewModelInterface::class);
        $viewModel->method('getBlock')->willReturn($block);

        $component = $this->createStub(ComponentInterface::class);
        $component->method('getViewModel')->willReturn($viewModel);

        $formRepository = $this->createStub(FormRepository::class);
        $formRepository->method('getComponent')->willReturn($component);

        return $formRepository;
    }
}
