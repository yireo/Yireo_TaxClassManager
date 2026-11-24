<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\TaxClassManager\Service\DefaultTaxClassGuard;

final class DefaultTaxClassGuardTest extends TestCase
{
    #[DataProvider('configProvider')]
    public function testDefaultTaxClassIsRefused(array $config): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('You cannot delete this tax class because it is used in the tax configuration');

        $this->createGuard($config)->assertCanBeDeleted(7);
    }

    public static function configProvider(): array
    {
        return [
            'default product class' => [['default||tax/classes/default_product_tax_class' => '7']],
            'default customer class' => [['default||tax/classes/default_customer_tax_class' => '7']],
            'website scope' => [['websites|1|tax/classes/default_product_tax_class' => '7']],
            'store scope' => [['stores|2|tax/classes/default_customer_tax_class' => '7']],
        ];
    }

    public function testOtherTaxClassCanBeDeleted(): void
    {
        $this->createGuard([
            'default||tax/classes/default_product_tax_class' => '2',
            'default||tax/classes/default_customer_tax_class' => '3',
            'stores|2|tax/classes/default_customer_tax_class' => '17',
        ])->assertCanBeDeleted(7);

        $this->addToAssertionCount(1);
    }

    private function createGuard(array $config): DefaultTaxClassGuard
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            fn (string $path, string $scopeType, mixed $scopeCode = null)
                => $config[$scopeType . '|' . $scopeCode . '|' . $path] ?? null
        );

        $website = $this->createStub(WebsiteInterface::class);
        $website->method('getId')->willReturn(1);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(2);

        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getWebsites')->willReturn([$website]);
        $storeManager->method('getStores')->willReturn([$store]);

        return new DefaultTaxClassGuard($scopeConfig, $storeManager);
    }
}
