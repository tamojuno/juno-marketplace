<?php

namespace DigitalHub\Juno\Controller\Notification;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NotFoundException;
use \Magento\Framework\Exception\LocalizedException;

class Status extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;
    /**
     * @var \DigitalHub\Juno\Model\Service\Api
     */
    private $junoApi;
    /**
     * @var \DigitalHub\Juno\Helper\Data
     */
    private $helper;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;
    /**
     * @var \DigitalHub\Juno\Logger\Logger
     */
    private $logger;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \DigitalHub\Juno\Model\Service\Api $junoApi,
        \DigitalHub\Juno\Helper\Data $helper,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \DigitalHub\Juno\Logger\Logger $logger,
        Context $context
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->junoApi = $junoApi;
        $this->helper = $helper;
        $this->invoiceService = $invoiceService;
        $this->logger = $logger;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData(true);

        $paymentToken = $this->getRequest()->getParam('paymentToken');
        $orderIncrementId = $this->getRequest()->getParam('chargeReference');

        $this->logger->info('POST NOTIFICATION', [$this->getRequest()->getParams()]);

        $order = $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
        if (!$order->getId()) {
            throw new NotFoundException(__('Pedido não encontrado para atualização'));
        }

        $isActive = (bool)$this->helper->getConfigData('digitalhub_juno_global', 'active', $order->getStoreId());

        if (!$isActive) {
            throw new LocalizedException(__('Módulo desativado nas configurações do Magento'));
        }

        $isSandbox = (bool)$this->helper->getConfigData('digitalhub_juno_global', 'sandbox', $order->getStoreId());

        $clientId = $this->helper->getConfigData('digitalhub_juno_global', 'client_id');
        $clientSecret = $this->helper->getConfigData('digitalhub_juno_global', 'client_secret');

        $accessToken = null;
        $basicAuthorization = base64_encode("{$clientId}:{$clientSecret}");

        try {
            $accessToken = $this->junoApi->getAccessToken($isSandbox, $basicAuthorization);
            $this->logger->info('Access Token Result', [$result]);
        } catch (\Exception $e) {
            return $resultJson;
        }

        if (!$accessToken) {
            return $resultJson;
        }

        // $paymentDetails = $this->junoApi->fetchPaymentDetails($isSandbox, $paymentToken);
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

        $paymentDetails = $this->junoApi->getPaymentDetails($isSandbox, $paymentToken, $headers);

        if ($paymentDetails['payment']['status'] == 'CONFIRMED') {
            if ($order->canInvoice() || $order->getState() == 'payment_review') {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $invoice->save();

                $order->setState('processing')->setStatus('processing');
                $order->addStatusToHistory(
                    $order->getStatus(),
                    'Pagamento confirmado automaticamente para o pedido #' . $order->getIncrementId(),
                    false
                );
                $order->save();
            }
        }

        return $resultJson;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
