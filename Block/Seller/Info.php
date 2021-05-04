<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   DigitalHub_Juno
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace DigitalHub\Juno\Block\Seller;

use Magento\Framework\View\Element\Template;

class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
    }

    public function getCustomerData()
    {
        return $this->customerSession->getCustomer();
    }
}
