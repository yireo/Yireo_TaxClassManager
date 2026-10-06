<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Controller\Adminhtml\Taxclass;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Psr\Log\LoggerInterface;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Yireo_TaxClassManager::tax_class_delete';

    /**
     * @param Context $context
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly TaxClassRepositoryInterface $taxClassRepository,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/grid');

        $ids = array_filter(array_map('intval', (array)$this->getRequest()->getParam('selected', [])));
        if (empty($ids)) {
            $this->messageManager->addErrorMessage(__('Please select tax classes to delete.'));
            return $resultRedirect;
        }

        $deletedCount = 0;
        foreach ($ids as $id) {
            try {
                if ($this->taxClassRepository->deleteById($id)) {
                    $deletedCount++;
                    continue;
                }

                $this->messageManager->addErrorMessage(__('Tax class %1 could not be deleted.', $id));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(__('Tax class %1: %2', $id, $e->getMessage()));
            } catch (Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Tax class %1 could not be deleted.', $id));
            }
        }

        if ($deletedCount > 0) {
            $this->messageManager->addSuccessMessage(__('%1 tax class(es) have been deleted.', $deletedCount));
        }

        return $resultRedirect;
    }
}
