<?php
namespace DigitalHub\Juno\Observer\CreditCard;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserver
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    const HASH = 'hash';
    const SAVE_CC = 'save_cc';
    const SAVED_CC_ID = 'saved_cc_id';
    const INSTALLMENTS = 'installments';
    const CC_LAST = 'cc_last';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::HASH,
        self::SAVE_CC,
        self::SAVED_CC_ID,
        self::INSTALLMENTS,
        self::CC_LAST,
    ];

    /**
     * @var \DigitalHub\Juno\Logger\Logger
     */
    private $logger;

    public function __construct(
        \DigitalHub\Juno\Logger\Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $this->logger->info('DATA ASSIGN');

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
