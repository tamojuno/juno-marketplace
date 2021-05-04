<?php
namespace DigitalHub\Juno\Gateway\Validator;

use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class CountryValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    /**
     * @var \DigitalHub\Juno\Helper\Data
     */
    private $helper;
    /**
     * @param ResultInterfaceFactory $resultFactory
     * @param \DigitalHub\Juno\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        \DigitalHub\Juno\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        parent::__construct($resultFactory);
    }
    /**
     * @param array $validationSubject
     * @return bool
     * @throws NotFoundException
     * @throws \Exception
     */
    public function validate(array $validationSubject)
    {
        $isValid = false;

        $country = $validationSubject['country'];

        // if ($country == 'BR') {
            $isValid = true;
        // }

        return $this->createResult($isValid);
    }
}
