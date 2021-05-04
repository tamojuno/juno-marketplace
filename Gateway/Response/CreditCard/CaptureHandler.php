<?php
namespace DigitalHub\Juno\Gateway\Response\CreditCard;

use Magento\Payment\Gateway\Response\HandlerInterface;

class CaptureHandler implements HandlerInterface
{
    protected $logger;
    protected $helper;

    public function __construct(
        \DigitalHub\Juno\Helper\Data $helper,
        \DigitalHub\Juno\Logger\Logger $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($handlingSubject);
        $payment = $payment->getPayment();

        $payment->setIsTransactionPending(false);
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
    }
}
