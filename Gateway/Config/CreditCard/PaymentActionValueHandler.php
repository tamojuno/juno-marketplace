<?php
namespace DigitalHub\Juno\Gateway\Config\CreditCard;

class PaymentActionValueHandler implements \Magento\Payment\Gateway\Config\ValueHandlerInterface
{
    private $helper;
    public function __construct(
        \DigitalHub\Juno\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }
    public function handle(array $subject, $storeId = null)
    {
//        $autoCapture = $this->helper->getConfigData('digitalhub_juno_global/creditcard', 'capture', $storeId);
//        if((bool)$autoCapture){
//            return 'authorize_capture';
//        }
//        return 'authorize';

          return 'authorize_capture';
    }
}
