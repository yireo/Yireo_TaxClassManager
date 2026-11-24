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
final class DeleteTest extends AbstractBackendController
{
    use CreateTaxClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = 'Yireo_TaxClassManager::tax_class_delete';
        $this->uri = 'backend/tax_class_manager/taxclass/delete';
        $this->httpMethod = HttpRequest::METHOD_POST;
    }

    public function testGetRequestIsNotAllowed(): void
    {
        $taxClassId = (int)$this->createTaxClass()->getClassId();

        $this->getRequest()->setMethod(HttpRequest::METHOD_GET);
        $this->dispatch($this->uri . '/id/' . $taxClassId);

        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
        $this->assertTrue($this->taxClassExists($taxClassId));
    }

    public function testPostRequestDeletesTaxClass(): void
    {
        $taxClassId = (int)$this->createTaxClass()->getClassId();

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue('id', $taxClassId);
        $this->dispatch($this->uri);

        $this->assertDeletedAndRedirected($taxClassId);
    }

    public function testPostRequestWithIdInUrlDeletesTaxClass(): void
    {
        $taxClassId = (int)$this->createTaxClass()->getClassId();

        $this->dispatchPost($this->uri . '/id/' . $taxClassId);

        $this->assertDeletedAndRedirected($taxClassId);
    }

    public function testDefaultTaxClassIsNotDeleted(): void
    {
        $taxClassId = (int)$this->createTaxClass(TaxClassManagementInterface::TYPE_PRODUCT)->getClassId();
        $this->setDefaultTaxClass('tax/classes/default_product_tax_class', $taxClassId);

        $this->dispatchPost($this->uri . '/id/' . $taxClassId);

        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages(
            $this->equalTo([
                'You cannot delete this tax class because it is used in the tax configuration'
                . ' as a default tax class. Choose another default tax class first.',
            ]),
            MessageInterface::TYPE_ERROR
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
        $this->assertTrue($this->taxClassExists($taxClassId));
    }

    public function testTaxClassInUseIsNotDeleted(): void
    {
        $taxClassId = $this->getTaxClassIdInUse();

        $this->dispatchPost($this->uri . '/id/' . $taxClassId);

        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages(
            $this->equalTo(['You cannot delete this tax class because it is used in existing customer group(s).']),
            MessageInterface::TYPE_ERROR
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
        $this->assertTrue($this->taxClassExists($taxClassId));
    }

    public function testMissingIdShowsError(): void
    {
        $this->dispatchPost($this->uri);

        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages(
            $this->equalTo(['We can&#039;t find a tax class to delete.']),
            MessageInterface::TYPE_ERROR
        );
    }

    public function testNonExistingIdShowsError(): void
    {
        $this->dispatchPost($this->uri . '/id/987654321');

        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages($this->countOf(1), MessageInterface::TYPE_ERROR);
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_SUCCESS);
    }

    private function dispatchPost(string $uri): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch($uri);
    }

    private function assertDeletedAndRedirected(int $taxClassId): void
    {
        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages(
            $this->equalTo(['The tax class has been deleted.']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $this->assertFalse($this->taxClassExists($taxClassId));
    }
}
