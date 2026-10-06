<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Controller\Adminhtml\Taxclass;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Grid extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Yireo_TaxClassManager::tax_class_view';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return Page
     */
    public function execute(): Page
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Yireo_TaxClassManager::tax_class');
        $resultPage->addBreadcrumb(__('Taxes'), __('Taxes'));
        $resultPage->addBreadcrumb(__('Tax Classes'), __('Tax Classes'));
        $resultPage->getConfig()->getTitle()->prepend(__('Tax Classes'));

        return $resultPage;
    }
}
