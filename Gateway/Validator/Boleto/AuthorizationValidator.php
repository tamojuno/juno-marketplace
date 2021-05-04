<?php

namespace DigitalHub\Juno\Gateway\Validator\Boleto;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use DigitalHub\Juno\Observer\CreditCard\DataAssignObserver;

class AuthorizationValidator extends AbstractValidator
{
    protected $helper;
    protected $logger;
    protected $eventManager;
    protected $checkoutSession;

    /**
     * AuthorizationValidator constructor.
     *
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \DigitalHub\Juno\Helper\Data $helper
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \DigitalHub\Juno\Helper\Data $helper,
        \DigitalHub\Juno\Logger\Logger $logger,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
        $this->checkoutSession = $checkoutSession;

        parent::__construct($resultFactory);
    }

    /**
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = \Magento\Payment\Gateway\Helper\SubjectReader::readResponse($validationSubject);

        $errorMessages = [];
        $errorMessage = null;
        $isValid = true;

        if (!isset($response['payment_result'])) {
            $errorMessage = 'Erro ao tentar autorizar o pagamento';
        } else {
            $result = $response['payment_result'];
            if (isset($result->error) && isset($result->details[0]->message)) {
                $errorMessage = $result->details[0]->message;
            } elseif (!isset($result->_embedded->charges[0]->id)) {
                $errorMessage = 'Erro ao tentar autorizar o pagamento';
            }
        }

        if ($errorMessage) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
