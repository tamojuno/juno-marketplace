<?php
namespace DigitalHub\Juno\Gateway\Request\CreditCard;

use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class CaptureDataBuilder
 */
class CaptureDataBuilder implements BuilderInterface
{
    private $helper;
    private $logger;

    /**
     * CaptureDataBuilder constructor.
     *
     * @param \DigitalHub\Juno\Helper\Data $helper
     * @param \DigitalHub\Juno\Helper\Data $logger
     */
    public function __construct(
        \DigitalHub\Juno\Helper\Data $helper,
        \DigitalHub\Juno\Logger\Logger $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Add shopper data into request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObject $paymentDataObject */
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($buildSubject);
        $amount =  \Magento\Payment\Gateway\Helper\SubjectReader::readAmount($buildSubject);

        $payment = $paymentDataObject->getPayment();
        $store_id = $payment->getOrder()->getStoreId();

        $request = [
            'store_id' => $store_id,
//            'payment_id' => null
        ];

        return $request;
    }
}
