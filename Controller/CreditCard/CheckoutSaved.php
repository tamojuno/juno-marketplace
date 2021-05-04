<?php
namespace DigitalHub\Juno\Controller\CreditCard;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class CheckoutSaved extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \DigitalHub\Juno\Helper\Data
     */
    private $helper;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var \DigitalHub\Juno\Model\CreditCard\TokenFactory
     */
    private $tokenFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \DigitalHub\Juno\Model\CreditCard\TokenFactory $tokenFactory,
        \Magento\Customer\Model\Session $customerSession,
        Context $context
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->tokenFactory = $tokenFactory;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        $tokenCollection = $this->tokenFactory->create()->getCollection();
        $tokenCollection->addFieldToFilter('customer_id', $this->customerSession->getCustomerId());
        $tokenCollection->setOrder('created_at', 'desc');
        $tokenCollection->load();

        $tokensResult = [
            ['label' => 'Utilizar um novo cartão de crédito', 'value' => '']
        ];

        foreach ($tokenCollection as $token) {
            $tokensResult[] = [
                'label' => 'xxxx-xxxx-xxxx-' . $token->getCcLast(),
                'value' => $token->getId(),
                'cc_last' => $token->getCcLast()
            ];
        }

        $result = $this->jsonFactory->create();
        $result->setData($tokensResult);

        return $result;
    }
}
