<?php
namespace DigitalHub\Juno\Controller\CreditCard;

use Magento\Framework\App\Action\Context;

class Saved extends \Magento\Framework\App\Action\Action
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
        Context $context
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->_redirect('/');
        }

        $page = $this->pageFactory->create();
        return $page;
    }
}
