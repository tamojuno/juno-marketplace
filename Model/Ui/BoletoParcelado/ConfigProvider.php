<?php

namespace DigitalHub\Juno\Model\Ui\BoletoParcelado;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Framework\Json\EncoderInterface;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'digitalhub_juno_boleto_parcelado';

    /**
     * @var AssetRepository
     */
    private $assetRepository;

    /**
     * @var \DigitalHub\Juno\Helper\Data
     */
    private $_junoHelper;

    /**
     * @var GatewayConfig
     */
    private $gatewayConfig;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * ConfigProvider constructor.
     *
     * @param AssetRepository $assetRepository
     * @param GatewayConfig $gatewayConfig
     * @param StoreManagerInterface $storeManager
     * @param EncoderInterface $encoder
     * @param \DigitalHub\Juno\Helper\Data $junoHelper
     */
    public function __construct(
        AssetRepository $assetRepository,
        GatewayConfig $gatewayConfig,
        StoreManagerInterface $storeManager,
        EncoderInterface $encoder,
        \DigitalHub\Juno\Helper\Data $junoHelper
    ) {
        $this->assetRepository = $assetRepository;
        $this->_junoHelper = $junoHelper;
        $this->gatewayConfig = $gatewayConfig;
        $this->storeId = $storeManager->getStore()->getId();
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $this->gatewayConfig->setMethodCode(self::CODE);
        $isActiveGlobal = $this->_junoHelper->getConfigData('digitalhub_juno_global', 'active', $this->storeId);
        $config = (bool)$this->_junoHelper->getConfigData('digitalhub_juno_boleto_parcelado', 'active', $this->storeId);
        return [
            'payment' => [
                'digitalhub_juno_boleto_parcelado' => [
                    'active' => $config && $isActiveGlobal
                ]
            ]
        ];
    }
}
