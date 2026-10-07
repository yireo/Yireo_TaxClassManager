<?php

declare(strict_types=1);

namespace Yireo\TaxClassManager\Test\Unit\Controller\Adminhtml\Taxclass;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractControllerTestCase extends TestCase
{
    protected RequestInterface&MockObject $request;
    protected ManagerInterface&MockObject $messageManager;
    protected Redirect&MockObject $redirect;
    protected Context&MockObject $context;
    protected array $requestParams = [];
    protected array $errorMessages = [];
    protected array $successMessages = [];
    protected array $redirectPaths = [];

    protected function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->request->method('getParam')->willReturnCallback(
            fn (string $name, mixed $default = null) => $this->requestParams[$name] ?? $default
        );

        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->messageManager->method('addErrorMessage')->willReturnCallback(
            function ($message) {
                $this->errorMessages[] = (string)$message;
                return $this->messageManager;
            }
        );
        $this->messageManager->method('addSuccessMessage')->willReturnCallback(
            function ($message) {
                $this->successMessages[] = (string)$message;
                return $this->messageManager;
            }
        );

        $this->redirect = $this->createMock(Redirect::class);
        $this->redirect->method('setPath')->willReturnCallback(
            function (string $path) {
                $this->redirectPaths[] = $path;
                return $this->redirect;
            }
        );

        $redirectFactory = $this->createMock(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($this->redirect);

        $this->context = $this->createMock(Context::class);
        $this->context->method('getRequest')->willReturn($this->request);
        $this->context->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->method('getResultRedirectFactory')->willReturn($redirectFactory);
    }

    protected function assertRedirectedToGrid(): void
    {
        $this->assertSame(['*/*/grid'], $this->redirectPaths);
    }
}
