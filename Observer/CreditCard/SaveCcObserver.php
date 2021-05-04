<?php
namespace DigitalHub\Juno\Observer\CreditCard;

use DigitalHub\Juno\Logger\Logger;
use Magento\Framework\Event\Observer;

class SaveCcObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;
    /**
     * @var \DigitalHub\Juno\Model\CreditCard\TokenFactory
     */
    private $tokenFactory;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \DigitalHub\Juno\Logger\Logger $logger,
        \DigitalHub\Juno\Model\CreditCard\TokenFactory $tokenFactory
    ) {
        $this->customerSession = $customerSession;
        $this->tokenFactory = $tokenFactory;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        if ($this->customerSession->isLoggedIn()) {
            $creditCardId = $observer->getCreditCardId();
            $ccLast = $observer->getPayment()->getAdditionalInformation('cc_last');
            $customerId = $this->customerSession->getCustomerId();

            if (!$creditCardId) {
                return true;
            }

            try {
                $token = $this->tokenFactory->create();
                $token->setData([
                    'credit_card_id' => $creditCardId,
                    'cc_last' => $ccLast,
                    'customer_id' => $customerId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $token->save();
            } catch (\Exception $e) {
                $this->logger->error('Erro ao tentar salvar token do cartão de crédito: ' . $e->getMessage());
            }
        }
    }
}
