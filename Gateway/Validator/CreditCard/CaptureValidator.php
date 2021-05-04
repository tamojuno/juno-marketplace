<?php
namespace DigitalHub\Juno\Gateway\Validator\CreditCard;

use Magento\Payment\Gateway\Validator\AbstractValidator;

class CaptureValidator extends AbstractValidator
{
    protected $logger;
    protected $helper;

    /**
     * CaptureValidator constructor.
     *
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \DigitalHub\Juno\Helper\Data $helper
     * @param \DigitalHub\Juno\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \DigitalHub\Juno\Helper\Data $helper,
        \DigitalHub\Juno\Logger\Logger $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;

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

        $transactionResult = $response['capture_result'];

        // TODO

        if (!$transactionResult->getId()) {
            $errorMessage = 'Erro ao tentar gerar captura';
        }

        if ($errorMessage) {
            $isValid = false;
            $errorMessages[] = $errorMessage;
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
