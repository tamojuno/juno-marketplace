<?php
namespace DigitalHub\Juno\Block\CreditCard;

use Magento\Framework\View\Element\Template;

class Saved extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \DigitalHub\Juno\Model\CreditCard\TokenFactory
     */
    private $tokenFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \DigitalHub\Juno\Model\CreditCard\TokenFactory $tokenFactory,
        \Magento\Customer\Model\Session $customerSession,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->tokenFactory = $tokenFactory;
        $this->customerSession = $customerSession;
    }

    public function getItems()
    {
        $tokenCollection = $this->tokenFactory->create()->getCollection();
        $tokenCollection->addFieldToFilter('customer_id', $this->customerSession->getCustomerId());
        $tokenCollection->setOrder('created_at', 'desc');
        $tokenCollection->load();

        return $tokenCollection;
    }
}
