<?php

namespace DigitalHub\Juno\Gateway\Request\Boleto;

use Magento\Payment\Gateway\Request\BuilderInterface;
use DigitalHub\Juno\Observer\CreditCard\DataAssignObserver;

class PaymentDataBuilder implements BuilderInterface
{
    private $helper;
    private $logger;
    private $checkoutSession;
    private $appState;

    public function __construct(
        \DigitalHub\Juno\Helper\Data $helper,
        \Magento\Framework\Model\Context $context,
        \DigitalHub\Juno\Logger\Logger $logger,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->appState = $context->getAppState();
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $additionalData = $payment->getAdditionalInformation();

        $this->logger->info('Payment Data Builder');

        $request = [];

        $expirationDays = (int)$this->helper
            ->getConfigData(
                'digitalhub_juno_global/boleto',
                'expiration_days',
                $order->getStoreId()
            );

        $maxOverdueDays = (int)$this->helper
            ->getConfigData(
                'digitalhub_juno_global/boleto',
                'max_overdue_days',
                $order->getStoreId()
            );

        $interest = $this->parseFloat(
            $this->helper->getConfigData(
                'digitalhub_juno_global/boleto',
                'interest',
                $order->getStoreId()
            )
        );

        $fine = $this->parseFloat(
            $this->helper->getConfigData(
                'digitalhub_juno_global/boleto',
                'fine',
                $order->getStoreId()
            )
        );

        $request['transaction']['charge'] = [
            'paymentTypes' => ['BOLETO'],
            'installments' => 1,
            'dueDate' => date('Y-m-d', strtotime('now +' . $expirationDays . 'days')),
            'interest' => $interest,
            'fine' => $fine,
            'maxOverdueDays' => $maxOverdueDays,
            'amount' => $order->getGrandTotal()
        ];

        return $request;
    }

    private function parseFloat($value)
    {
        $value = trim($value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (float)$value;
    }
}
