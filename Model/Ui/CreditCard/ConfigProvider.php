<?php
namespace DigitalHub\Juno\Model\Ui\CreditCard;

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
    const CODE = 'digitalhub_juno_creditcard';

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

        $isActiveGlobal = (int)$this->_junoHelper->getConfigData('digitalhub_juno_global', 'active');
        $active = (int)($this->_junoHelper
                ->getConfigData('digitalhub_juno_global/creditcard', 'active') && $isActiveGlobal);
        $canSaveCc = (int)$this->_junoHelper->getConfigData('digitalhub_juno_global/creditcard', 'can_save_cc');
        $title = $this->_junoHelper->getConfigData('digitalhub_juno_global/creditcard', 'title');

        return [
            'payment' => [
                'digitalhub_juno_creditcard' => [
                    'active' => (bool)$active,
                    'can_save_cc' => (bool)$canSaveCc,
                    'title' => $title
                ]
            ]
        ];
    }
}
