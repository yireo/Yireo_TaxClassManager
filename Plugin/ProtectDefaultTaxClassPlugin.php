<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Plugin;

use Loki\AdminComponents\Component\Form\FormRepository;
use Loki\AdminComponents\Exception\FormActionException;
use Loki\AdminComponents\Form\Action\DeleteAction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Yireo\TaxClassManager\Service\DefaultTaxClassGuard;

class ProtectDefaultTaxClassPlugin
{
    public const FORM_BLOCK_NAME = 'yireo-tax-class-manager.form';

    /**
     * @param DefaultTaxClassGuard $defaultTaxClassGuard
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private readonly DefaultTaxClassGuard $defaultTaxClassGuard,
        private readonly ManagerInterface $messageManager
    ) {
    }

    /**
     * @param DeleteAction $subject
     * @param FormRepository $formRepository
     * @param array $value
     * @return null
     */
    public function beforeExecute(DeleteAction $subject, FormRepository $formRepository, array $value)
    {
        $block = $formRepository->getComponent()->getViewModel()->getBlock();
        if ($block->getNameInLayout() !== self::FORM_BLOCK_NAME) {
            return null;
        }

        $taxClassId = (int)($value['item']['class_id'] ?? 0);
        if ($taxClassId < 1) {
            return null;
        }

        try {
            $this->defaultTaxClassGuard->assertCanBeDeleted($taxClassId);
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            throw new FormActionException($exception->getMessage(), 0, $exception);
        }

        return null;
    }
}
