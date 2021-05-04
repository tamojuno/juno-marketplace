<?php
namespace DigitalHub\Juno\Gateway\Http\Client\BoletoParcelado;

use DigitalHub\Juno\Model\Service\Api;
use Magento\Payment\Gateway\Http\ClientInterface;

/**
 * Class TransactionSale
 */
class TransactionAuthorization implements ClientInterface
{

    protected $helper;
    protected $logger;
    protected $_appState;
    protected $_storeManager;
    /**
     * @var Api
     */
    private $junoApi;

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
        \DigitalHub\Juno\Model\Service\Api $junoApi,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->_encryptor = $encryptor;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->_appState = $context->getAppState();
        $this->_storeManager = $storeManager;
        $this->junoApi = $junoApi;
    }

    /**
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     * @return mixed
     * @throws ClientException
     */
    public function placeRequest(\Magento\Payment\Gateway\Http\TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $isSandbox = (int)$this->helper->getConfigData('digitalhub_juno_global', 'sandbox');

        $this->logger->info('Transaction Authorization', [$request]);

        $response = [];

        try {
            $result = $this->junoApi->issueCharge($isSandbox, $request['transaction']);

            $this->logger->info('Transaction RESULT', [$result]);

            $response['payment_result'] = $result;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }
}
