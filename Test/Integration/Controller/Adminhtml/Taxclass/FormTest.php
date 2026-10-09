<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration\Controller\Adminhtml\Taxclass;

use Magento\Framework\Message\MessageInterface;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Yireo\TaxClassManager\Test\Integration\FixtureTrait\CreateTaxClass;

#[DbIsolation(true)]
final class FormTest extends AbstractBackendController
{
    use CreateTaxClass;

    private const CLASS_TYPE_SELECT = '#<select[^>]+data-name="class_type"#s';

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = 'Yireo_TaxClassManager::tax_class_save';
        $this->uri = 'backend/tax_class_manager/taxclass/form';
    }

    public function testNewFormRendersEditableTypeSelect(): void
    {
        $this->dispatch($this->uri);

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());

        $body = (string)$this->getResponse()->getBody();
        $this->assertStringContainsString('New Tax Class', $body);
        $this->assertStringContainsString('data-name="class_name"', $body);
        $this->assertMatchesRegularExpression(self::CLASS_TYPE_SELECT, $body);
        $this->assertStringContainsString('-- Select a type --', $body);
        $this->assertStringContainsString('value="' . TaxClassManagementInterface::TYPE_CUSTOMER . '"', $body);
        $this->assertStringContainsString('value="' . TaxClassManagementInterface::TYPE_PRODUCT . '"', $body);
        $this->assertStringContainsString($this->button('saveAndCloseAction'), $body);
        $this->assertStringNotContainsString($this->button('deleteAction'), $body);
        $this->assertStringNotContainsString($this->button('saveAndContinueAction'), $body);
    }

    public function testEditFormRendersReadOnlyTypeAndExtraButtons(): void
    {
        $taxClass = $this->createTaxClass(TaxClassManagementInterface::TYPE_PRODUCT);

        $this->dispatch($this->uri . '/id/' . $taxClass->getClassId());

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());

        $body = (string)$this->getResponse()->getBody();
        $this->assertStringContainsString('Edit Tax Class', $body);
        $this->assertStringContainsString($taxClass->getClassName(), $body);
        $this->assertStringContainsString('data-name="class_name"', $body);
        $this->assertDoesNotMatchRegularExpression(self::CLASS_TYPE_SELECT, $body);
        $this->assertStringContainsString($this->button('saveAndCloseAction'), $body);
        $this->assertStringContainsString($this->button('deleteAction'), $body);
        $this->assertStringContainsString($this->button('saveAndContinueAction'), $body);
    }

    private function button(string $method): string
    {
        return '@click.prevent="' . $method . '"';
    }

    public function testMissingTaxClassRedirectsToGrid(): void
    {
        $this->dispatch($this->uri . '/id/987654321');

        $this->assertRedirect($this->stringContains('tax_class_manager/taxclass/grid'));
        $this->assertSessionMessages(
            $this->equalTo(['This tax class no longer exists.']),
            MessageInterface::TYPE_ERROR
        );
    }
}
