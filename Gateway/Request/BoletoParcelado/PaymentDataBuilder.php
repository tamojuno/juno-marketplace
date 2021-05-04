<?php

namespace DigitalHub\Juno\Gateway\Request\BoletoParcelado;

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
        $installments = (int)$additionalData[\DigitalHub\Juno\Observer\CreditCard\DataAssignObserver::INSTALLMENTS];

        $this->logger->info('Payment Data Builder');

        $request = [];

        $expirationDays = (int)$this->helper
            ->getConfigData(
                'digitalhub_juno_global/boleto_parcelado',
                'expiration_days',
                $order->getStoreId()
            );

        $maxOverdueDays = (int)$this->helper
            ->getConfigData(
                'digitalhub_juno_global/boleto_parcelado',
                'max_overdue_days',
                $order->getStoreId()
            );

        $interest = $this->parseFloat(
            $this->helper
                ->getConfigData(
                    'digitalhub_juno_global/boleto_parcelado',
                    'interest',
                    $order->getStoreId()
                )
        );
        $fine = $this->parseFloat(
            $this->helper->getConfigData(
                'digitalhub_juno_global/boleto_parcelado',
                'fine',
                $order->getStoreId()
            )
        );

        $request['transaction'] = [
            'paymentTypes' => 'BOLETO',
            'installments' => $installments,
            'dueDate' => date('d/m/Y', strtotime('now +' . $expirationDays . 'days')),
            'interest' => $interest,
            'fine' => $fine,
            'maxOverdueDays' => $maxOverdueDays
        ];

        if ($installments > 1) {
            $request['transaction']['totalAmount'] = $order->getGrandTotal();
        } else {
            $request['transaction']['amount'] = $order->getGrandTotal();
        }

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
