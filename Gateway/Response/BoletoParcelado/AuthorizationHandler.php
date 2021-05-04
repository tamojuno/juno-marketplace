<?php
namespace DigitalHub\Juno\Gateway\Response\BoletoParcelado;

use Magento\Payment\Gateway\Response\HandlerInterface;

class AuthorizationHandler implements HandlerInterface
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

        $this->logger->info('AUTHORIZATION HANDLER', [$response]);

        $paymentResult = $response['payment_result'];

        try {
            $payment->setTransactionId($paymentResult->data->charges[0]->code);
            $payment->setAdditionalInformation('juno_data', json_encode($paymentResult));

            $payment->setIsTransactionPending(true);

            // important
            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);

            // send order confirmation mail
            $payment->getOrder()->setCanSendNewEmailFlag(true);
        } catch (\Exception $e) {
            $this->logger->info('AUTHORIZATION HANDLER ERROR', [$e->getMessage()]);
        }
    }
}
