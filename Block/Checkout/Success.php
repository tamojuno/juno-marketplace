<?php
namespace DigitalHub\Juno\Block\Checkout;

use Magento\Framework\View\Element\Template;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    public function __construct(
        Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
    }

    public function getOrder()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        return $order;
    }
}
