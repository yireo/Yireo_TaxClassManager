<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Integration\ViewModel\Options;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Yireo\TaxClassManager\Test\Integration\FixtureTrait\CreateTaxClass;
use Yireo\TaxClassManager\ViewModel\Options\ClassTypeOptions;

#[DbIsolation(true)]
final class ClassTypeOptionsTest extends TestCase
{
    use CreateTaxClass;

    public function testCanBeUsedAsLayoutArgument(): void
    {
        $classTypeOptions = Bootstrap::getObjectManager()->get(ClassTypeOptions::class);

        $this->assertInstanceOf(OptionSourceInterface::class, $classTypeOptions);
        $this->assertInstanceOf(ArgumentInterface::class, $classTypeOptions);
    }

    public function testEveryOptionIsAcceptedByTheTaxClassRepository(): void
    {
        $classTypeOptions = Bootstrap::getObjectManager()->get(ClassTypeOptions::class);

        foreach ($classTypeOptions->toOptionArray() as $option) {
            $taxClass = $this->createTaxClass($option['value']);

            $this->assertSame($option['value'], $taxClass->getClassType());
            $this->assertSame((string)$option['label'], $classTypeOptions->getLabel($taxClass->getClassType()));
        }
    }
}
