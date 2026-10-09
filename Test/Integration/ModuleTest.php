<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration;

use Magento\Backend\Model\Menu\Config as MenuConfig;
use Magento\Framework\Acl\Builder as AclBuilder;
use Magento\Framework\App\Route\ConfigInterface as RouteConfig;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsEnabled;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegistered;
use Yireo\IntegrationTestHelper\Test\Integration\Traits\AssertModuleIsRegisteredForReal;

final class ModuleTest extends TestCase
{
    use AssertModuleIsEnabled;
    use AssertModuleIsRegistered;
    use AssertModuleIsRegisteredForReal;

    public function testIfModuleIsEnabled(): void
    {
        $requiredModules = [
            'Magento_Tax',
            'Loki_AdminComponents',
            'Yireo_TaxClassManager',
        ];

        foreach ($requiredModules as $moduleName) {
            $this->assertModuleIsRegistered($moduleName);
            $this->assertModuleIsEnabled($moduleName);
        }
    }

    public function testAclResourcesExist(): void
    {
        $aclBuilder = Bootstrap::getObjectManager()->get(AclBuilder::class);
        $aclBuilder->resetRuntimeAcl();
        $acl = $aclBuilder->getAcl();

        $aclResources = [
            'Yireo_TaxClassManager::tax_class',
            'Yireo_TaxClassManager::tax_class_view',
            'Yireo_TaxClassManager::tax_class_save',
            'Yireo_TaxClassManager::tax_class_delete',
        ];

        foreach ($aclResources as $aclResource) {
            $this->assertTrue($acl->hasResource($aclResource), 'ACL resource "' . $aclResource . '" not found');
            $this->assertTrue($acl->inheritsResource($aclResource, 'Magento_Tax::manage_tax'));
        }
    }

    public function testAdminRouteIsRegistered(): void
    {
        $routeConfig = Bootstrap::getObjectManager()->get(RouteConfig::class);

        $this->assertSame('tax_class_manager', $routeConfig->getRouteFrontName('tax_class_manager', 'adminhtml'));
        $this->assertContains(
            'Yireo_TaxClassManager',
            $routeConfig->getModulesByFrontName('tax_class_manager', 'adminhtml')
        );
    }

    #[AppArea('adminhtml')]
    public function testMenuItemIsAddedBelowTaxes(): void
    {
        $menu = Bootstrap::getObjectManager()->get(MenuConfig::class)->getMenu();

        $taxMenu = $menu->get('Magento_Tax::sales_tax');
        $this->assertNotNull($taxMenu, 'Menu item "Magento_Tax::sales_tax" not found');

        $menuItem = $taxMenu->getChildren()->get('Yireo_TaxClassManager::tax_class');
        $this->assertNotNull($menuItem, 'Menu item "Yireo_TaxClassManager::tax_class" not found below "Taxes"');

        $menuItemData = $menuItem->toArray();
        $this->assertSame('tax_class_manager/taxclass/grid', $menuItemData['action']);
        $this->assertSame('Yireo_TaxClassManager::tax_class_view', $menuItemData['resource']);
    }
}
