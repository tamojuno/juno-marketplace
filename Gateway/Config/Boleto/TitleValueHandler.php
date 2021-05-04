<?php
namespace DigitalHub\Juno\Gateway\Config\Boleto;

use Magento\Quote\Model\QuoteFactory;

class TitleValueHandler implements \Magento\Payment\Gateway\Config\ValueHandlerInterface
{
    private $helper;
    private $logger;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    public function __construct(
        \DigitalHub\Juno\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \DigitalHub\Juno\Logger\Logger $logger
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->quoteFactory = $quoteFactory;
    }
    public function handle(array $subject, $storeId = null)
    {
        return $this->helper->getConfigData('digitalhub_juno_global/boleto', 'title', $storeId);
    }
}
