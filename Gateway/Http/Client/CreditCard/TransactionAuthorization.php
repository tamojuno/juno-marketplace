<?php
namespace DigitalHub\Juno\Gateway\Http\Client\CreditCard;

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
        $response = [];

        $isSandbox = (int)$this->helper->getConfigData('digitalhub_juno_global', 'sandbox');
        $clientId = $this->helper->getConfigData('digitalhub_juno_global', 'client_id');
        $clientSecret = $this->helper->getConfigData('digitalhub_juno_global', 'client_secret');

        $accessToken = null;
        $basicAuthorization = base64_encode("{$clientId}:{$clientSecret}");

        try {
            $accessToken = $this->junoApi->getAccessToken($isSandbox, $basicAuthorization);
            $this->logger->info('Access Token Result', [$accessToken]);
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            return $response;
        }

        if (!$accessToken) {
            $response['error'] = __('Unable to authenticate');
            return $response;
        }

        $this->logger->info('Transaction Authorization', [$request]);

        try {
            // $result = $this->junoApi->issueCharge($isSandbox, $request['transaction']);
            $result = $this->doTransaction($isSandbox, $accessToken, $request['transaction']);
		$this->logger->info('Pre Transaction RESULT', [$result]);
            if ($result->_embedded->charges[0]->id) {
                $paymentParams = $request['payment_request'];
                $paymentParams['chargeId'] = $result->_embedded->charges[0]->id;
                $result = $this->createPayment($isSandbox, $accessToken, $paymentParams);
            } else {
                $response['error'] = __('Transaction not found');
                return $response;
            }

            $this->logger->info('Transaction RESULT', [$result]);

            $response['payment_creditcard_id'] = $request['payment_request']['creditCardDetails']['creditCardId'];
            $response['payment_result'] = $result;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    private function createPayment($isSandbox, $accessToken, $paymentParams)
    {
        $headers = [];
        $privateToken = 'sandbox_private_token';
        if (!$isSandbox) {
            $privateToken = 'production_private_token';
        }

        $headers['X-Resource-Token'] = $this->helper->getConfigData(
            'digitalhub_juno_global',
            $privateToken
        );

        $headers['Authorization'] = "Bearer {$accessToken}";
        return $this->junoApi->createPayment($isSandbox, $headers, $paymentParams);
    }

    private function doTransaction($isSandbox, $accessToken, $transaction)
    {
        $headers = [];
        $privateToken = 'sandbox_private_token';
        if (!$isSandbox) {
            $privateToken = 'production_private_token';
        }

        $headers['X-Resource-Token'] = $this->helper->getConfigData(
            'digitalhub_juno_global',
            $privateToken
        );

        $headers['Authorization'] = "Bearer {$accessToken}";
        return $this->junoApi->callCharge($isSandbox, $headers, $transaction);
    }
}
