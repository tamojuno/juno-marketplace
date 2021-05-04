<?php
namespace DigitalHub\Juno\Gateway\Config\BoletoParcelado;

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
        return 'authorize';
    }
}
