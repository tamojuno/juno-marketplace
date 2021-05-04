<?php

namespace DigitalHub\Juno\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    private $pricingHelper;
    private $logger;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \DigitalHub\Juno\Logger\Logger $logger,
        Context $context
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->pricingHelper = $pricingHelper;
        $this->logger = $logger;
    }

    public function getConfigData($area, $field, $storeId = null)
    {
        return $this->scopeConfig
            ->getValue('payment/' . $area . '/' . $field, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isSandbox()
    {
        return (int)$this->getConfigData('digitalhub_juno_global', 'sandbox');
    }

    public function getCustomerDocumentNumber(\Magento\Sales\Model\Order $order)
    {
        $isLogged = $this->customerSession->isLoggedIn();
        $customer = $this->customerSession->getCustomer();
        $attributeValue = null;

        // for logged user
        if ($isLogged) {
            $mappedAttributeCodeCpf = $this->getConfigData(
                'digitalhub_juno_global/attributes_mapping',
                'customer_cpf',
                $order->getStoreId()
            );

            $mappedAttributeCodeCnpj = $this->getConfigData(
                'digitalhub_juno_global/attributes_mapping',
                'customer_cnpj',
                $order->getStoreId()
            );

            $attributeTypeCpf = strpos($mappedAttributeCodeCpf, 'address_') === 0 ? 'address' : 'customer';
            $attributeTypeCnpj = strpos($mappedAttributeCodeCpf, 'address_') === 0 ? 'address' : 'customer';

            $mappedAttributeCodeCpf = str_replace($attributeTypeCpf . '_', '', $mappedAttributeCodeCpf);
            $mappedAttributeCodeCnpj = str_replace($attributeTypeCnpj . '_', '', $mappedAttributeCodeCnpj);

            // from customer, logged
            if ($mappedAttributeCodeCpf && $attributeTypeCpf == 'customer') {
                // cpf
                if ($customer->getData($mappedAttributeCodeCpf)) {
                    $attributeValue = $customer->getData($mappedAttributeCodeCpf);
                }
                $customerDataModel = $customer->getDataModel();
                if ($customerDataModel->getCustomAttribute($mappedAttributeCodeCpf)
                    && $customerDataModel->getCustomAttribute($mappedAttributeCodeCpf)->getValue()) {
                    $attributeValue = $customerDataModel->getCustomAttribute($mappedAttributeCodeCpf)->getValue();
                }
            }

            if ($mappedAttributeCodeCnpj && $attributeTypeCnpj == 'customer') {
                // cnpj
                if ($customer->getData($mappedAttributeCodeCnpj)) {
                    $attributeValue = $customer->getData($mappedAttributeCodeCnpj);
                }
                $customerDataModel = $customer->getDataModel();
                if ($customerDataModel->getCustomAttribute($mappedAttributeCodeCnpj)
                    && $customerDataModel->getCustomAttribute($mappedAttributeCodeCnpj)->getValue()) {
                    $attributeValue = $customerDataModel->getCustomAttribute($mappedAttributeCodeCnpj)->getValue();
                }
            }

            // from address
            $address = $order->getBillingAddress();

            if ($mappedAttributeCodeCpf && $attributeTypeCpf == 'address') {
                // cpf
                if ($address->getData($mappedAttributeCodeCpf)) {
                    $attributeValue = $address->getData($mappedAttributeCodeCpf);
                }
            }

            if ($mappedAttributeCodeCnpj && $attributeTypeCnpj == 'address') {
                // cnpj
                if ($address->getData($mappedAttributeCodeCnpj)) {
                    $attributeValue = $address->getData($mappedAttributeCodeCnpj);
                }
            }
        }

        if (!$isLogged) {
            $mappedAttributeCodeCpfGuest = $this->getConfigData(
                'digitalhub_juno_global/attributes_mapping',
                'customer_cpf_guest',
                $order->getStoreId()
            );
            $mappedAttributeCodeCnpjGuest = $this->getConfigData(
                'digitalhub_juno_global/attributes_mapping',
                'customer_cnpj_guest',
                $order->getStoreId()
            );

            $mappedAttributeCodeCpfGuest = str_replace('address_', '', $mappedAttributeCodeCpfGuest);
            $mappedAttributeCodeCnpjGuest = str_replace('address_', '', $mappedAttributeCodeCnpjGuest);

            // from address
            $address = $order->getBillingAddress();

            // cpf
            if ($mappedAttributeCodeCpfGuest && $address->getData($mappedAttributeCodeCpfGuest)) {
                $attributeValue = $address->getData($mappedAttributeCodeCpfGuest);
            }

            // cnpj
            if ($mappedAttributeCodeCnpjGuest && $address->getData($mappedAttributeCodeCnpjGuest)) {
                $attributeValue = $address->getData($mappedAttributeCodeCnpjGuest);
            }
        }

        $attributeValue = str_replace(".","",$attributeValue);
        $attributeValue = str_replace("-","",$attributeValue);

        return $attributeValue ? preg_replace('/\D+/', '', $attributeValue) : null;
    }

    public function getCustomerTelephone(\Magento\Sales\Model\Order $order)
    {
        $isLogged = $this->customerSession->isLoggedIn();
        $customer = $this->customerSession->getCustomer();
        $attributeValue = null;

        // for logged user
        if ($isLogged) {
            $mappedAttributeCodeTelephone = $this->getConfigData(
                'digitalhub_juno_global/attributes_mapping',
                'customer_telephone',
                $order->getStoreId()
            );
            $attributeTypeTelephone = strpos($mappedAttributeCodeTelephone, 'address_') === 0 ? 'address' : 'customer';
            $mappedAttributeCodeTelephone = str_replace(
                $attributeTypeTelephone . '_',
                '',
                $mappedAttributeCodeTelephone
            );

            // from customer, logged
            if ($attributeTypeTelephone && $attributeTypeTelephone == 'customer') {
                if ($customer->getData($mappedAttributeCodeTelephone)) {
                    $attributeValue = $customer->getData($mappedAttributeCodeTelephone);
                }
                $customerDataModel = $customer->getDataModel();
                if ($customerDataModel->getCustomAttribute($mappedAttributeCodeTelephone)
                    && $customerDataModel->getCustomAttribute($mappedAttributeCodeTelephone)->getValue()) {
                    $attributeValue = $customerDataModel->getCustomAttribute($mappedAttributeCodeTelephone)->getValue();
                }
            }

            // from address
            $address = $order->getBillingAddress();

            if ($attributeTypeTelephone && $attributeTypeTelephone == 'address') {
                if ($address->getData($mappedAttributeCodeTelephone)) {
                    $attributeValue = $address->getData($mappedAttributeCodeTelephone);
                }
            }
        }

        if (!$isLogged) {
            $mappedAttributeCodeTelephoneGuest = $this->getConfigData(
                'digitalhub_juno_global/attributes_mapping',
                'customer_telephone_guest',
                $order->getStoreId()
            );
            $mappedAttributeCodeTelephoneGuest = str_replace('address_', '', $mappedAttributeCodeTelephoneGuest);

            // from address
            $address = $order->getBillingAddress();

            if ($mappedAttributeCodeTelephoneGuest && $address->getData($mappedAttributeCodeTelephoneGuest)) {
                $attributeValue = $address->getData($mappedAttributeCodeTelephoneGuest);
            }
        }

        return $attributeValue ? preg_replace('/\D+/', '', $attributeValue) : null;
    }

    public function getCustomerAddressAttribute(\Magento\Sales\Model\Order $order, $field)
    {
        $attributeValue = null;
        $mappedAttributeCode = $this->getConfigData(
            'digitalhub_juno_global/attributes_mapping',
            $field,
            $order->getStoreId()
        );
        $mappedAttributeCode = str_replace('address_', '', $mappedAttributeCode);

        // from address
        $address = $order->getBillingAddress();

        if ($address->getData($mappedAttributeCode)) {
            $attributeValue = $address->getData($mappedAttributeCode);
        }

        $street_lines_map = [
            'street_1' => 0,
            'street_2' => 1,
            'street_3' => 2,
            'street_4' => 3
        ];

        if (strpos($mappedAttributeCode, 'street_') !== false) {
            $street = $address->getStreet();
            $attributeValue = $street[$street_lines_map[$mappedAttributeCode]] ?? null;
        }

        return $attributeValue ? $attributeValue : null;
    }

    public function getCreditCardInstallments()
    {
        $total = $this->checkoutSession->getQuote()->getGrandTotal();
        $maxInstallments = (int)$this->getConfigData('digitalhub_juno_global/creditcard', 'max_installments');
        $minInstallmentValue = (float)$this->getConfigData(
            'digitalhub_juno_global/creditcard',
            'min_installment_value'
        );

        $installments = [
            [
                'label' => '1x de ' . $this->formatPrice($total),
                'value' => 1
            ]
        ];

        for ($i = 1; $i <= $maxInstallments; $i++) {
            if ($i == 1) {
                continue;
            }

            $installmentValue = $total / $i;
            if ($installmentValue >= $minInstallmentValue) {
                $installments[] = [
                    'label' => $i . 'x de ' . $this->formatPrice($installmentValue),
                    'value' => $i
                ];
            }
        }

        return $installments;
    }

    public function getBoletoParceladoInstallments()
    {
        $total = $this->checkoutSession->getQuote()->getGrandTotal();
        $maxInstallments = (int)$this->getConfigData('digitalhub_juno_global/boleto_parcelado', 'max_installments');
        $minInstallmentValue = (float)$this->getConfigData(
            'digitalhub_juno_global/boleto_parcelado',
            'min_installment_value'
        );

        $installments = [
            [
                'label' => '1x de ' . $this->formatPrice($total),
                'value' => 1
            ]
        ];

        for ($i = 1; $i <= $maxInstallments; $i++) {
            if ($i == 1) {
                continue;
            }

            $installmentValue = $total / $i;
            if ($installmentValue >= $minInstallmentValue) {
                $installments[] = [
                    'label' => $i . 'x de ' . $this->formatPrice($installmentValue),
                    'value' => $i
                ];
            }
        }

        return $installments;
    }

    public function getNotificationUrl()
    {
        return $this->_urlBuilder->getBaseUrl() . 'digitalhub_juno/notification/status';
    }

    public function formatPrice($price)
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    public function _parseToCamelCase($string)
    {
        $words = explode('_', $string);
        $words = array_map("ucwords", $words);
        return implode('', $words);
    }
}
