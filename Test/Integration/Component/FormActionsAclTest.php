<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration\Component;

use Laminas\Http\Header\GenericHeader;
use Laminas\Http\Headers;
use Loki\Components\Util\Security\AjaxSignature;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\ClassModel;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Yireo\TaxClassManager\Test\Integration\FixtureTrait\CreateTaxClass;

/**
 * Post form actions to the Loki Components endpoint, like the tax class form does
 */
#[DbIsolation(true)]
#[AppIsolation(true)]
#[AppArea('adminhtml')]
final class FormActionsAclTest extends AbstractBackendController
{
    use CreateTaxClass;

    private const FORM_BLOCK = 'yireo-tax-class-manager.form';

    protected $uri = 'backend/loki_components/index/html';

    public function testDeleteActionDeletesTaxClass(): void
    {
        $taxClassId = (int)$this->createTaxClass()->getClassId();

        $this->dispatchFormAction($taxClassId, ['actions' => ['delete'], 'item' => ['class_id' => $taxClassId]]);

        $this->assertFalse($this->taxClassExists($taxClassId));
    }

    public function testDeleteActionRequiresDeletePermission(): void
    {
        $taxClassId = (int)$this->createTaxClass()->getClassId();
        $this->denyResource('Yireo_TaxClassManager::tax_class_delete');

        $this->dispatchFormAction($taxClassId, ['actions' => ['delete'], 'item' => ['class_id' => $taxClassId]]);

        $this->assertTrue($this->taxClassExists($taxClassId));
    }

    public function testDeleteActionRefusesDefaultTaxClass(): void
    {
        $taxClassId = (int)$this->createTaxClass(TaxClassManagementInterface::TYPE_PRODUCT)->getClassId();
        $this->setDefaultTaxClass('tax/classes/default_product_tax_class', $taxClassId);

        $this->dispatchFormAction($taxClassId, ['actions' => ['delete'], 'item' => ['class_id' => $taxClassId]]);

        $this->assertTrue($this->taxClassExists($taxClassId));
    }

    public function testSaveActionRenamesTaxClass(): void
    {
        $taxClass = $this->createTaxClass();
        $taxClassId = (int)$taxClass->getClassId();

        $this->dispatchFormAction($taxClassId, [
            'actions' => ['save'],
            'item' => ['class_id' => $taxClassId, 'class_name' => 'Renamed ' . uniqid()],
        ]);

        $this->assertStringStartsWith('Renamed ', $this->loadFromDatabase($taxClassId)->getClassName());
    }

    public function testSaveActionRequiresSavePermission(): void
    {
        $taxClass = $this->createTaxClass();
        $taxClassId = (int)$taxClass->getClassId();
        $this->denyResource('Yireo_TaxClassManager::tax_class_save');

        $this->dispatchFormAction($taxClassId, [
            'actions' => ['save'],
            'item' => ['class_id' => $taxClassId, 'class_name' => 'Renamed ' . uniqid()],
        ]);

        $this->assertSame(
            $taxClass->getClassName(),
            $this->loadFromDatabase($taxClassId)->getClassName()
        );
    }

    public function testSaveActionDoesNotChangeClassType(): void
    {
        $taxClassId = (int)$this->createTaxClass(TaxClassManagementInterface::TYPE_CUSTOMER)->getClassId();

        $this->dispatchFormAction(
            $taxClassId,
            [
                'actions' => ['save'],
                'item' => [
                    'class_id' => $taxClassId,
                    'class_name' => 'Renamed ' . uniqid(),
                    'class_type' => TaxClassManagementInterface::TYPE_PRODUCT,
                ],
            ],
            false
        );

        $this->assertSame(
            TaxClassManagementInterface::TYPE_CUSTOMER,
            $this->loadFromDatabase($taxClassId)->getClassType()
        );
    }

    private function dispatchFormAction(int $taxClassId, array $update, bool $withEditHandle = true): void
    {
        $handles = ['default', 'tax_class_manager_taxclass_form'];
        if ($withEditHandle) {
            $handles[] = 'yireo_taxclassmanager_taxclass_form_edit';
        }

        $payload = [
            'targets' => [],
            'handles' => $handles,
            'pageHandles' => [],
            'updates' => [['blockName' => self::FORM_BLOCK, 'update' => $update]],
            'request' => ['params' => ['id' => $taxClassId]],
        ];

        $payload['signature'] = $this->_objectManager->get(AjaxSignature::class)->sign([
            'handles' => $payload['handles'],
            'pageHandles' => $payload['pageHandles'],
            'request' => $payload['request'],
        ]);

        $headers = new Headers();
        $headers->addHeader(GenericHeader::fromString('X-Alpine-Request: 1'));

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setMethod(HttpRequest::METHOD_POST);
        $request->setContent((string)json_encode($payload));

        $this->dispatch($this->uri);
    }

    private function loadFromDatabase(int $taxClassId): ClassModel
    {
        return $this->_objectManager->create(ClassModel::class)->load($taxClassId);
    }

    private function denyResource(string $resource): void
    {
        $this->_objectManager->get(AclBuilder::class)->getAcl()->deny($this->_auth->getUser()->getRoles(), $resource);
    }
}
