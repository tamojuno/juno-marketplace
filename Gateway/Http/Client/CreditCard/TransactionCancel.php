<?php
namespace DigitalHub\Juno\Gateway\Http\Client\CreditCard;

use Magento\Payment\Gateway\Http\ClientInterface;

/**
 * Class TransactionCancel
 */
class TransactionCancel implements ClientInterface
{

    protected $helper;
    protected $logger;
    protected $_appState;
    protected $_storeManager;

    /**
     * PaymentRequest constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \DigitalHub\Juno\Logger\Logger $logger
     * @param \DigitalHub\Juno\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \DigitalHub\Juno\Logger\Logger $logger,
        \DigitalHub\Juno\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_encryptor = $encryptor;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->_appState = $context->getAppState();
        $this->_storeManager = $storeManager;
    }

    /**
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     * @return mixed
     * @throws ClientException
     */
    public function placeRequest(\Magento\Payment\Gateway\Http\TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $response = [];
        $storeId = $request['store_id'];

        try {
            // TODO
            $response['cancel_result'] = null;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
