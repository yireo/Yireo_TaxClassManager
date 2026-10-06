<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Controller\Adminhtml\Taxclass;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Psr\Log\LoggerInterface;

class Delete extends Action implements HttpGetActionInterface, HttpPostActionInterface
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

        $id = (int)$this->getRequest()->getParam('id');
        if ($id < 1) {
            $this->messageManager->addErrorMessage(__('We can\'t find a tax class to delete.'));
            return $resultRedirect;
        }

        try {
            if ($this->taxClassRepository->deleteById($id)) {
                $this->messageManager->addSuccessMessage(__('The tax class has been deleted.'));
                return $resultRedirect;
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect;
        } catch (Exception $e) {
            $this->logger->critical($e);
        }

        $this->messageManager->addErrorMessage(__('The tax class could not be deleted.'));

        return $resultRedirect;
    }
}
