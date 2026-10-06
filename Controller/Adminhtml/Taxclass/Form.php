<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Controller\Adminhtml\Taxclass;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Tax\Api\TaxClassRepositoryInterface;

class Form extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Yireo_TaxClassManager::tax_class_save';

    private const EDIT_HANDLE = 'yireo_taxclassmanager_taxclass_form_edit';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param TaxClassRepositoryInterface $taxClassRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly TaxClassRepositoryInterface $taxClassRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page|Redirect
     */
    public function execute(): Page|Redirect
    {
        $id = (int)$this->getRequest()->getParam('id');
        if ($id > 0) {
            try {
                $this->taxClassRepository->get($id);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This tax class no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/grid');
            }
        }

        $title = $id > 0 ? __('Edit Tax Class') : __('New Tax Class');

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        if ($id > 0) {
            $resultPage->addHandle(self::EDIT_HANDLE);
        }

        $resultPage->setActiveMenu('Yireo_TaxClassManager::tax_class');
        $resultPage->addBreadcrumb(__('Taxes'), __('Taxes'));
        $resultPage->addBreadcrumb(__('Tax Classes'), __('Tax Classes'));
        $resultPage->addBreadcrumb($title, $title);
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
