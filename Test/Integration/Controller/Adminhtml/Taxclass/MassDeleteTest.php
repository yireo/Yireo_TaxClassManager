<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration\Controller\Adminhtml\Taxclass;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Yireo\TaxClassManager\Test\Integration\FixtureTrait\CreateTaxClass;

#[DbIsolation(true)]
#[AppIsolation(true)]
final class MassDeleteTest extends AbstractBackendController
{
    use CreateTaxClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = 'Yireo_TaxClassManager::tax_class_delete';
        $this->uri = 'backend/tax_class_manager/taxclass/massDelete';
        $this->httpMethod = HttpRequest::METHOD_POST;
    }

    public function testSelectedTaxClassesAreDeleted(): void
    {
        $customerClassId = (int)$this->createTaxClass(TaxClassManagementInterface::TYPE_CUSTOMER)->getClassId();
        $productClassId = (int)$this->createTaxClass(TaxClassManagementInterface::TYPE_PRODUCT)->getClassId();

        $this->dispatchWithSelection([$customerClassId, $productClassId]);

        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages(
            $this->equalTo(['2 tax class(es) have been deleted.']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $this->assertFalse($this->taxClassExists($customerClassId));
        $this->assertFalse($this->taxClassExists($productClassId));
    }

    public function testTaxClassInUseIsReportedWhileOthersAreDeleted(): void
    {
        $deletableClassId = (int)$this->createTaxClass()->getClassId();
        $classIdInUse = $this->getTaxClassIdInUse();

        $this->dispatchWithSelection([$deletableClassId, $classIdInUse]);

        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages(
            $this->equalTo(['1 tax class(es) have been deleted.']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertSessionMessages(
            $this->equalTo([
                'Tax class ' . $classIdInUse
                . ': You cannot delete this tax class because it is used in existing customer group(s).',
            ]),
            MessageInterface::TYPE_ERROR
        );
        $this->assertFalse($this->taxClassExists($deletableClassId));
        $this->assertTrue($this->taxClassExists($classIdInUse));
    }

    public function testDefaultTaxClassIsReportedWhileOthersAreDeleted(): void
    {
        $deletableClassId = (int)$this->createTaxClass()->getClassId();
        $defaultClassId = (int)$this->createTaxClass()->getClassId();
        $this->setDefaultTaxClass('tax/classes/default_customer_tax_class', $defaultClassId, 'stores', 'default');

        $this->dispatchWithSelection([$deletableClassId, $defaultClassId, $deletableClassId]);

        $this->assertSessionMessages(
            $this->equalTo(['1 tax class(es) have been deleted.']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertSessionMessages(
            $this->equalTo([
                'Tax class ' . $defaultClassId . ': You cannot delete this tax class because it is used in the'
                . ' tax configuration as a default tax class. Choose another default tax class first.',
            ]),
            MessageInterface::TYPE_ERROR
        );
        $this->assertFalse($this->taxClassExists($deletableClassId));
        $this->assertTrue($this->taxClassExists($defaultClassId));
    }

    public function testEmptySelectionShowsError(): void
    {
        $this->dispatchWithSelection([]);

        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages(
            $this->equalTo(['Please select tax classes to delete.']),
            MessageInterface::TYPE_ERROR
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
    }

    public function testGetRequestIsNotAllowed(): void
    {
        $taxClassId = (int)$this->createTaxClass()->getClassId();

        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->getRequest()->setParam('selected', [$taxClassId]);
        $this->dispatch($this->uri);

        $this->assertTrue($this->taxClassExists($taxClassId));
    }

    private function dispatchWithSelection(array $ids): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue('selected', $ids);
        $this->dispatch($this->uri);
    }
}
