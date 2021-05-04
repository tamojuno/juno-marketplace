<?php
namespace DigitalHub\Juno\Controller\Seller;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;

class Config extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $pageFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Customer\Model\Session $customerSession,
        Context $context,
        \Webkul\Marketplace\Helper\Data $marketplaceHelper
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->customerSession = $customerSession;
        $this->marketplaceHelper = $marketplaceHelper;
    }

    /**
     * Check customer authentication
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_objectManager->get('Magento\Customer\Model\Url')->getLoginUrl();

        if (!$this->customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }


    public function execute()
    {
        $isPartner = $this->marketplaceHelper->isSeller();
        if ($isPartner == 1) {
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->pageFactory->create();
            if ($this->marketplaceHelper->getIsSeparatePanel()) {
                $resultPage->addHandle('layout2_juno_account_navigation');
            }
            $resultPage->getConfig()->getTitle()->set(__('Configuração da Juno'));
            return $resultPage;
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/dashboard',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }

    }
}
