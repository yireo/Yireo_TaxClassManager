<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Controller\Adminhtml\Taxclass;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Yireo\TaxClassManager\Controller\Adminhtml\Taxclass\Delete;

final class DeleteTest extends AbstractControllerTestCase
{
    private TaxClassRepositoryInterface&MockObject $taxClassRepository;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxClassRepository = $this->createMock(TaxClassRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testAdminResource(): void
    {
        $this->assertSame('Yireo_TaxClassManager::tax_class_delete', Delete::ADMIN_RESOURCE);
    }

    #[DataProvider('invalidIdProvider')]
    public function testInvalidIdShowsErrorWithoutDeleting(mixed $id): void
    {
        $this->requestParams = ['id' => $id];
        $this->taxClassRepository->expects($this->never())->method('deleteById');

        $result = $this->createController()->execute();

        $this->assertSame($this->redirect, $result);
        $this->assertSame(['We can\'t find a tax class to delete.'], $this->errorMessages);
        $this->assertSame([], $this->successMessages);
        $this->assertRedirectedToGrid();
    }

    public static function invalidIdProvider(): array
    {
        return [
            'missing' => [null],
            'zero' => ['0'],
            'negative' => ['-3'],
            'non-numeric' => ['abc'],
        ];
    }

    public function testSuccessfulDelete(): void
    {
        $this->requestParams = ['id' => '42'];
        $this->taxClassRepository->expects($this->once())->method('deleteById')->with(42)->willReturn(true);
        $this->logger->expects($this->never())->method('critical');

        $result = $this->createController()->execute();

        $this->assertSame($this->redirect, $result);
        $this->assertSame(['The tax class has been deleted.'], $this->successMessages);
        $this->assertSame([], $this->errorMessages);
        $this->assertRedirectedToGrid();
    }

    public function testRepositoryReturningFalseShowsGenericError(): void
    {
        $this->requestParams = ['id' => '42'];
        $this->taxClassRepository->method('deleteById')->willReturn(false);

        $this->createController()->execute();

        $this->assertSame(['The tax class could not be deleted.'], $this->errorMessages);
        $this->assertSame([], $this->successMessages);
        $this->assertRedirectedToGrid();
    }

    public function testLocalizedExceptionShowsItsMessage(): void
    {
        $this->requestParams = ['id' => '42'];
        $this->taxClassRepository->method('deleteById')
            ->willThrowException(new LocalizedException(new Phrase('Tax class is in use.')));
        $this->logger->expects($this->never())->method('critical');

        $this->createController()->execute();

        $this->assertSame(['Tax class is in use.'], $this->errorMessages);
        $this->assertSame([], $this->successMessages);
        $this->assertRedirectedToGrid();
    }

    public function testGenericExceptionIsLoggedAndShowsGenericError(): void
    {
        $exception = new Exception('Database is gone');
        $this->requestParams = ['id' => '42'];
        $this->taxClassRepository->method('deleteById')->willThrowException($exception);
        $this->logger->expects($this->once())->method('critical')->with($exception);

        $this->createController()->execute();

        $this->assertSame(['The tax class could not be deleted.'], $this->errorMessages);
        $this->assertSame([], $this->successMessages);
        $this->assertRedirectedToGrid();
    }

    private function createController(): Delete
    {
        return new Delete($this->context, $this->taxClassRepository, $this->logger);
    }
}
