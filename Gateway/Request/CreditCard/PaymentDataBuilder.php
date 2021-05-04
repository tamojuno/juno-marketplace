<?php

namespace DigitalHub\Juno\Gateway\Request\CreditCard;

use DigitalHub\Juno\Model\CreditCard\TokenFactory;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Request\BuilderInterface;
use DigitalHub\Juno\Observer\CreditCard\DataAssignObserver;

class PaymentDataBuilder implements BuilderInterface
{
    private $helper;
    private $logger;
    private $checkoutSession;
    private $appState;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var TokenFactory
     */
    private $tokenFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \DigitalHub\Juno\Helper\Data $helper,
        \Magento\Framework\Model\Context $context,
        \DigitalHub\Juno\Logger\Logger $logger,
        \Magento\Framework\Registry $registry,
        \DigitalHub\Juno\Model\CreditCard\TokenFactory $tokenFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->appState = $context->getAppState();
        $this->registry = $registry;
        $this->tokenFactory = $tokenFactory;
        $this->customerSession = $customerSession;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $additionalData = $payment->getAdditionalInformation();
        $installments = (int)$additionalData[\DigitalHub\Juno\Observer\CreditCard\DataAssignObserver::INSTALLMENTS];
        $saveCcActive = (boolean)$this->helper
            ->getConfigData('digitalhub_juno_global/creditcard', 'can_save_cc', $order->getStoreId());
        $saveCc = $saveCcActive
            ? $additionalData[\DigitalHub\Juno\Observer\CreditCard\DataAssignObserver::SAVE_CC]
            : null;
        $savedCcId = $additionalData[\DigitalHub\Juno\Observer\CreditCard\DataAssignObserver::SAVED_CC_ID];
        $junoCreditCardId = null;

        if ($savedCcId) {
            $token = $this->tokenFactory->create()->load($savedCcId);
            if ($token->getCustomerId() == $this->customerSession->getCustomerId()) {
                $junoCreditCardId = $token->getCreditCardId();
            }
        }

        $this->logger->info('Payment Data Builder');

        $request['transaction']['charge']['paymentTypes'] = ['CREDIT_CARD'];
        $request['transaction']['charge']['installments'] = $installments;

        $creditCardDetails = [
            'creditCardHash' => !$junoCreditCardId ? $additionalData[DataAssignObserver::HASH] : null,
            'creditCardId' => $junoCreditCardId
        ];

        $request['payment_request']['creditCardDetails'] = $creditCardDetails;

        if ($saveCc) {
            $this->registry->register('digitalhub_juno_save_cc', $saveCc);
        }

        if ($installments > 1) {
            $request['transaction']['charge']['totalAmount'] = $order->getGrandTotal();
        } else {
            $request['transaction']['charge']['amount'] = $order->getGrandTotal();
        }

        return $request;
    }
}
