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
use Yireo\TaxClassManager\Controller\Adminhtml\Taxclass\MassDelete;

final class MassDeleteTest extends AbstractControllerTestCase
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
        $this->assertSame('Yireo_TaxClassManager::tax_class_delete', MassDelete::ADMIN_RESOURCE);
    }

    #[DataProvider('emptySelectionProvider')]
    public function testEmptySelectionShowsErrorWithoutDeleting(array $params): void
    {
        $this->requestParams = $params;
        $this->taxClassRepository->expects($this->never())->method('deleteById');

        $result = $this->createController()->execute();

        $this->assertSame($this->redirect, $result);
        $this->assertSame(['Please select tax classes to delete.'], $this->errorMessages);
        $this->assertSame([], $this->successMessages);
        $this->assertRedirectedToGrid();
    }

    public static function emptySelectionProvider(): array
    {
        return [
            'missing parameter' => [[]],
            'empty array' => [['selected' => []]],
            'only invalid ids' => [['selected' => ['0', 'abc', '']]],
            'only ALL marker' => [['selected' => ['ALL']]],
        ];
    }

    public function testAllSelectedIdsAreDeleted(): void
    {
        $this->requestParams = ['selected' => ['3', '5', '7']];
        $deletedIds = [];
        $this->taxClassRepository->expects($this->exactly(3))->method('deleteById')
            ->willReturnCallback(function ($id) use (&$deletedIds) {
                $deletedIds[] = $id;
                return true;
            });

        $result = $this->createController()->execute();

        $this->assertSame($this->redirect, $result);
        $this->assertSame([3, 5, 7], $deletedIds);
        $this->assertSame(['3 tax class(es) have been deleted.'], $this->successMessages);
        $this->assertSame([], $this->errorMessages);
        $this->assertRedirectedToGrid();
    }

    public function testSelectAllMarkerIsIgnored(): void
    {
        $this->requestParams = ['selected' => ['ALL', '3', '5']];
        $deletedIds = [];
        $this->taxClassRepository->method('deleteById')
            ->willReturnCallback(function ($id) use (&$deletedIds) {
                $deletedIds[] = $id;
                return true;
            });

        $this->createController()->execute();

        $this->assertSame([3, 5], $deletedIds);
        $this->assertSame(['2 tax class(es) have been deleted.'], $this->successMessages);
        $this->assertSame([], $this->errorMessages);
    }

    public function testSingleScalarIdIsAccepted(): void
    {
        $this->requestParams = ['selected' => '9'];
        $this->taxClassRepository->expects($this->once())->method('deleteById')->with(9)->willReturn(true);

        $this->createController()->execute();

        $this->assertSame(['1 tax class(es) have been deleted.'], $this->successMessages);
    }

    public function testMixedResultsReportPerIdAndCountOnlySuccesses(): void
    {
        $genericException = new Exception('Database is gone');
        $this->requestParams = ['selected' => ['1', '2', '3', '4', '5']];
        $this->taxClassRepository->method('deleteById')->willReturnCallback(
            function (int $id) use ($genericException) {
                return match ($id) {
                    1, 5 => true,
                    2 => false,
                    3 => throw new LocalizedException(new Phrase('Tax class is in use.')),
                    4 => throw $genericException,
                };
            }
        );
        $this->logger->expects($this->once())->method('critical')->with($genericException);

        $result = $this->createController()->execute();

        $this->assertSame($this->redirect, $result);
        $this->assertSame(
            [
                'Tax class 2 could not be deleted.',
                'Tax class 3: Tax class is in use.',
                'Tax class 4 could not be deleted.',
            ],
            $this->errorMessages
        );
        $this->assertSame(['2 tax class(es) have been deleted.'], $this->successMessages);
        $this->assertRedirectedToGrid();
    }

    public function testNoSuccessMessageWhenNothingWasDeleted(): void
    {
        $this->requestParams = ['selected' => ['1', '2']];
        $this->taxClassRepository->method('deleteById')
            ->willThrowException(new LocalizedException(new Phrase('Tax class is in use.')));
        $this->logger->expects($this->never())->method('critical');

        $this->createController()->execute();

        $this->assertSame(
            ['Tax class 1: Tax class is in use.', 'Tax class 2: Tax class is in use.'],
            $this->errorMessages
        );
        $this->assertSame([], $this->successMessages);
        $this->assertRedirectedToGrid();
    }

    private function createController(): MassDelete
    {
        return new MassDelete($this->context, $this->taxClassRepository, $this->logger);
    }
}
