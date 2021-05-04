<?php
namespace DigitalHub\Juno\Controller\CreditCard;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Installments extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \DigitalHub\Juno\Helper\Data
     */
    private $helper;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \DigitalHub\Juno\Helper\Data $helper,
        Context $context
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $installments = $this->helper->getCreditCardInstallments();

        $result = $this->jsonFactory->create();
        $result->setData($installments);

        return $result;
    }
}
