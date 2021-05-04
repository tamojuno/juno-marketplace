<?php

namespace DigitalHub\Juno\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;

class SellerPayment extends AbstractHelper
{
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Webkul\Marketplace\Helper\Payment $mpPaymentHelper,
        \Magento\Quote\Model\Quote $quoteModel,
        \DigitalHub\Juno\Logger\Logger $logger,
        CustomerRepositoryInterface $customerRepository,
        \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory $mpProductCollection,
        \Webkul\Marketplace\Helper\Data $mpHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Context $context
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->pricingHelper = $pricingHelper;
        $this->mpPaymentHelper = $mpPaymentHelper;
        $this->quoteModel = $quoteModel;
        $this->logger = $logger;
        $this->_customerRepository = $customerRepository;
        $this->mpProductCollection = $mpProductCollection;
        $this->mpHelper = $mpHelper;
        $this->priceCurrency = $priceCurrency;
        $this->storeManager = $storeManager;
    }

    public function getPrivateToken()
    {
        try {
            $sandbox = $this->scopeConfig->getValue(
                'payment/digitalhub_juno_global/sandbox',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $tokenField = 'sandbox_private_token';
            if (!$sandbox) {
                $tokenField = 'production_private_token';
            }

            return $this->scopeConfig->getValue(
                'payment/digitalhub_juno_global/'.$tokenField,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } catch (\Exception $e) {
            $this->logger->info("Helper_Data getPrivateToken : ".$e->getMessage());
        }
    }

    public function getShippingTaxClass()
    {
        try {
            return $this->scopeConfig->getValue(
                'tax/classes/shipping_tax_class',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } catch (\Exception $e) {
            $this->logger->info("Helper_Data getShippingTaxClass : ".$e->getMessage());
        }
    }


    public function formatPrice($price, $fromCurrency)
    {
        try {
            $rates = $this->storeManager->getStore()->getBaseCurrency()->getRate($fromCurrency);
            $price = $price / $rates;
            return $this->priceCurrency->round($price);
        } catch (\Exception $e) {
            $this->logger->info("Helper_Data formatPrice : ".$e->getMessage());
        }
    }



    public function calculateSellerPaymentData($quoteId)
    {
        $order = $this->quoteModel->load($quoteId);
        $shippingData = $this->mpPaymentHelper->getShippingData($order);

        $newvar = $shippingData['newvar'];
        $shippingTaxAmount = $shippingData['shippingTaxAmount'];
        $shippingAmount = $shippingData['shippingAmount'];
        $shipinf = $shippingData['shipinf'];
        $customName = $shippingData['customName'];
        $customerAddress = $shippingData['customerAddress'];

        $this->_eventManager->dispatch(
            'mp_advance_commission_rule',
            ['order' => $order]
        );

        $cartData = $this->getCartData(
            $order,
            $newvar,
            $shipinf,
            $shippingTaxAmount
        );

        $cart = $cartData['cart'];

        $adminShipTaxAmt = $cartData['adminShipTaxAmt'];
        $commission = $cartData['commission'];

        $finalCartData = $this->getFinalCart(
            $cart,
            $newvar,
            $adminShipTaxAmt,
            $shippingTaxAmount,
            $shippingAmount,
            $commission
        );
        $totalsellercount = $finalCartData['totalsellercount'];
        $finalcart = $finalCartData['finalcart'];
        // $this->logger->info('final cart final', $finalcart);
        // $this->logger->info('totalsellercount', [$totalsellercount]);
        if ($totalsellercount == 1) {
            return [];
        }
        $bodyparams = $this->getRequestParams(
            $finalcart,
            $totalsellercount,
            $order
        );

        return $bodyparams;

    }

    public function getRequestParams(
        $finalcart,
        $totalsellercount,
        $order
    ) {
        try {
            $updatedData = $this->getUpdatedFinalCart($finalcart, $totalsellercount);
            $finalcart = $updatedData['finalcart'];

            $bodyparams = [];

            foreach ($finalcart as $partner) {
                if ($partner['price'] != 0) {
                    if (!isset($partner['primary'])) {
                        $partner['primary'] = false;
                    }
                    // if ($this->mpHelper->getCurrentCurrencyCode() !== $order->getBaseCurrencyCode()) {
                    //     $partner['price'] = $this->formatPrice(
                    //         $partner['price'],
                    //         $this->mpHelper->getCurrentCurrencyCode()
                    //     );
                    // }
                    $temp = [
                        "recipientToken" => $partner['junoToken'],
                        "amount" => $partner['price'],
                        "amountRemainder" => $partner['primary'],
                        "chargeFee" => $partner['primary'],
                    ];
                    $bodyparams[] = $temp;
                }
            }
            return $bodyparams;
        } catch (\Exception $e) {
            $this->logger->info("Model_PaymentMethod getRequestParams : ".$e->getMessage());
        }
    }

    public function getUpdatedFinalCart($finalcart, $totalsellercount)
    {
        try {
            $adminToken = $this->getPrivateToken();
            if (count($finalcart) > 1) {
                $totalamount = 0;
                $count = 0;
                foreach ($finalcart as $partner) {
                    if ($partner['price']) {
                        $totalamount += $partner['price'];
                    }
                }

                foreach ($finalcart as $partner) {
                    if ($partner['junoToken'] == $adminToken
                        && $partner['seller'] == 0
                    ) {
                        $finalcart[$count]['price'] = $partner['price'];
                        if ($totalsellercount > 1) {
                            $finalcart[$count]['primary'] = true;
                        }
                    }
                    ++$count;
                }
            }

            return [
                'finalcart' => $finalcart
            ];
        } catch (\Exception $e) {
            $this->logger->info("Model_PaymentMethod getUpdatedFinalCart : ".$e->getMessage());
        }
    }



    public function getFinalCart(
        $cart,
        $newvar,
        $adminShipTaxAmt,
        $shippingTaxAmount,
        $shippingAmount,
        $commission
    ) {
        $status = 0;
        $index = 0;
        $counter = 0;
        $adminShippingTax = 0;
        $quoteshipPrice = 0;
        $count = 0;

        $adminToken = $this->getPrivateToken();
        $createdFinalCart = $this->createFinalCart($cart);
        // $this->logger->info('final cart', $createdFinalCart);
        $finalcart = $createdFinalCart['finalcart'];

        $sellerTaxToAdmin = $createdFinalCart['sellerTaxToAdmin'];
        $totalsellerpaytoadmin = $createdFinalCart['totalsellerpaytoadmin'];
        $adminTotalTax = $createdFinalCart['adminTotalTax'];
        $sellertax = $createdFinalCart['sellertax'];

        foreach ($finalcart as $cart) {
            if ($cart['seller'] == 0) {
                $status = 1;
                $index = $counter;
            }
            ++$counter;
        }

        if ($newvar != 'webkul') {
            if ($this->getShippingTaxClass()
                && ((int)$adminShipTaxAmt == 0
                || $adminShipTaxAmt == null)
            ) {
                $adminShippingTax = $shippingTaxAmount;
            }
            $quoteshipPrice = $shippingAmount;
        }
        if ($status == 1) {
            $finalcart[$index]['price'] += $quoteshipPrice + $commission + $adminShippingTax;
        } else {
            if ($newvar == '') {
                $finalcart[$counter]['price'] = $quoteshipPrice + $commission + $adminShippingTax;
            } else {
                $finalcart[$counter]['price'] = $commission;
            }
            $finalcart[$counter]['seller'] = 0;
            $finalcart[$counter]['junoToken'] = $adminToken;
            $finalcart[$counter]['primary'] = false;
            $finalcart[$counter]['discount'] = 0;
        }
        $totalsellercount = count($finalcart);
        foreach ($finalcart as $partner) {
            if (isset($partner['discount']) && $partner['discount'] < 0) {
                $finalcart[$count]['price'] += $partner['discount'];
            }
            if ($partner['junoToken'] == $adminToken) {
                if ($finalcart[$count]['seller'] == 0) {
                    if ($adminTotalTax == 0) {
                        $finalcart[$count]['price'] += $totalsellerpaytoadmin;
                        if ($sellertax !== 0
                            && (int)$sellerTaxToAdmin == 1
                        ) {
                            $finalcart[$count]['price'] += $sellertax;
                        }
                    } else {
                        $finalcart[$count]['price'] += $totalsellerpaytoadmin + $adminTotalTax;
                    }
                } else {
                    $finalcart[$count]['price'] = 0;
                    --$totalsellercount;
                }
            }
            ++$count;
        }

        return [
            'totalsellercount' => $totalsellercount,
            'finalcart' => $finalcart
        ];
    }

    public function createFinalCart($cart)
    {
        try {
            $adminTotalTax = 0;
            $i = 0;
            $finalcart = $junoData = [];
            $totalsellerpaytoadmin = 0;
            $totalSellersTax = 0;
            $junoAdminToken = $this->getPrivateToken();
            foreach ($cart as $item) {
                $temp = explode(',', $item['data']);
                $sellertax = 0;

                $junoData = $this->getTokenAndSellerAmount($temp, $totalsellerpaytoadmin);
                $junoToken = $junoData['junoToken'];
                $sellerTaxToAdmin = $junoData['sellerTaxToAdmin'];
                $totalsellerpaytoadmin = $junoData['totalsellerpaytoadmin'];

                if (!$this->mpHelper->getConfigTaxManage()) {
                    $adminTotalTax += $temp[5];
                } else {
                    $sellertax = $temp[5];
                }
                if ($junoToken == $junoAdminToken && (int)$temp[0]!==0) {
                    $totalSellersTax += $sellertax;
                }
                $totalDiscountAmount = 0;
                if ($temp[0]!==0) {
                    $couponAmount = $this->mpPaymentHelper->getSellerCouponAmount($temp[0]);
                    $creditPoints = $this->mpPaymentHelper->getCreditPoints($temp[0]);
                    $totalDiscountAmount = $couponAmount + $creditPoints;
                }

                if ($i == 0) {
                    $finalcart[$i]['price'] = $temp[2] + $sellertax;
                    $finalcart[$i]['seller'] = $temp[0];
                    $finalcart[$i]['junoToken'] = $junoToken;
                    $finalcart[$i]['discount'] = $totalDiscountAmount;
                    ++$i;
                } else {
                    if ($temp[0] == $finalcart[$i - 1]['seller']) {
                        $finalcart[$i - 1]['price'] += $sellertax + $temp[2];
                    } else {
                        $finalcart[$i]['price'] = $temp[2] + $sellertax;
                        $finalcart[$i]['seller'] = $temp[0];
                        $finalcart[$i]['junoToken'] = $junoToken;
                        $finalcart[$i]['discount'] = $totalDiscountAmount;
                        ++$i;
                    }
                }
            }
            return [
                'finalcart' => $finalcart,
                'sellerTaxToAdmin' => $sellerTaxToAdmin,
                'totalsellerpaytoadmin' => $totalsellerpaytoadmin,
                'adminTotalTax' => $adminTotalTax,
                'sellertax' => $totalSellersTax
            ];
        } catch (\Exception $e) {
            $this->logger->info("Model_PaymentMethod createFinalCart : ".$e->getMessage());
        }
    }

    public function getTokenAndSellerAmount($temp, $totalsellerpaytoadmin)
    {
        try {
            $sellerTaxToAdmin = 0;
            $adminToken = $this->getPrivateToken();
            $token = $adminToken;
            if ($temp[0] != 0) {
                $sellerToken = '';
                $seller = $this->_customerRepository->getById($temp[0]);
                if ($seller->getId() && !empty($seller->getCustomAttribute('seller_juno_recipient_token')->getValue())) {
                    $sellerToken = $seller->getCustomAttribute('seller_juno_recipient_token')->getValue();
                }
                if ($sellerToken) {
                    $token = $sellerToken;
                    if ($sellerToken == $adminToken) {
                        $sellerTaxToAdmin = 1;
                        $token = $adminToken;
                        $totalsellerpaytoadmin += $temp[2];
                    }
                } else {
                    $sellerTaxToAdmin = 1;
                    $totalsellerpaytoadmin += $temp[2];
                }
            }
            return [
                'junoToken' => $token,
                'sellerTaxToAdmin' => $sellerTaxToAdmin,
                'totalsellerpaytoadmin' => $totalsellerpaytoadmin
            ];
        } catch (\Exception $e) {
            $this->logger->info("Model_PaymentMethod getTokenAndSellerAmount : ".$e->getMessage());
        }
    }



    public function getCartData(
        $order,
        $newvar,
        $shipinf,
        $shippingTaxAmount
    ) {
        $cart = [];
        $commission = 0;
        $adminShipTaxAmt = 0;
        $i = 0;
        $sellerId = '';
        try {
            $cartItems = $order->getAllVisibleItems();
            foreach ($cartItems as $item) {
                $shippingprice = 0;
                $invoiceprice = $item->getRowTotal();
                $itemId = $item->getProductId();
                /*if ($this->helper->isModuleEnabled('Webkul_MpAssignProduct')
                    && $this->helper->isOutputEnabled('Webkul_MpAssignProduct')
                ) {
                    $mpassignModel = $this->_objectManager->create(
                        'Webkul\MpAssignProduct\Model\Quote'
                    )->getCollection()
                        ->addFieldToFilter(
                            'item_id',
                            $item->getId()
                        )->addFieldToFilter(
                            'quote_id',
                            $item->getQuoteId()
                        )->addFieldToFilter(
                            'product_id',
                            $item->getProductId()
                        );
                    if ($mpassignModel->getSize()) {
                        foreach ($mpassignModel as $mpassignProduct) {
                            $sellerId = $mpassignProduct->getSellerId();
                        }
                    }
                }

                $commissionData = $this->getCommissionData($item, $sellerId);*/

                $commissionData = $this->mpPaymentHelper->getCommissionData($item);
                $commissionData = $this->updateCommissionData($commissionData);
                $tempcoms = $commissionData['tempcoms'];
                $commissionDetail = $commissionData['commissionDetail'];

                $commission += $tempcoms;
                $price = $invoiceprice - $tempcoms;
                $itemDiscountAmount = 0;
                if ($item->getDiscountAmount() > 0) {
                    $itemDiscountAmount = $item->getDiscountAmount();
                    $price = $price - $itemDiscountAmount;
                }

                if ($newvar == 'webkul') {
                    $custid = 0;
                    if ($sellerId=='') {
                        $customr = $this->mpProductCollection->create()
                            ->addFieldToFilter(
                                'mageproduct_id',
                                $itemId
                            );
                        foreach ($customr as $user) {
                            $custid = $user->getSellerId();
                        }
                    } else {
                        $custid = $sellerId;
                    }
                    foreach ($shipinf as $k => $key) {
                        if ($key['seller'] == $custid) {
                            $price += $key['amount'];
                            $shippingprice = $key['amount'];
                            $shipinf[$k]['amount'] = 0;
                        }
                    }
                }

                if (!isset($commissionDetail['id'])) {
                    $commissionDetail['id'] = 0;
                }

                if ($shippingTaxAmount !== 0) {
                    $adminShipTaxAmt = 1;
                    $tempArray = [
                        $commissionDetail['id'],
                        $item->getProductId(),
                        $price,
                        $invoiceprice,
                        $shippingprice,
                        ($item->getTaxAmount() + $shippingTaxAmount)
                    ];
                    $cart[$i]['data'] = implode(",", $tempArray);
                } else {
                    $tempArray = [
                        $commissionDetail['id'],
                        $item->getProductId(),
                        $price,
                        $invoiceprice,
                        $shippingprice,
                        $item->getTaxAmount()
                    ];
                    $cart[$i]['data'] = implode(",", $tempArray);
                }
                ++$i;
            }
            asort($cart);
        } catch (\Exception $e) {
            $this->logger->info("Model_PaymentMethod getCartData : ".$e->getMessage());
        }
        return [
            'cart' => $cart,
            'adminShipTaxAmt' => $adminShipTaxAmt,
            'commission' => $commission
        ];
    }

    public function updateCommissionData($commissionData)
    {
        try {
            $tempcoms = $commissionData['tempcoms'];
            $commissionDetail = $commissionData['commissionDetail'];
            if (!$tempcoms) {
                $commissionDetail = $this->mpPaymentHelper->getSellerDetail($commissionData['product_id']);

                if ($commissionDetail['id'] !== 0) {
                    $junoToken = $this->getSellerToken($commissionDetail['id']);
                    if (!$junoToken) {
                        $commissionDetail['id'] = 0;
                        $commissionDetail['commission'] = 0;
                    }
                }

                if ($commissionDetail['id'] !== 0
                    && $commissionDetail['commission'] !== 0
                ) {
                    $tempcoms = round(
                        ($commissionData['row_total'] * $commissionDetail['commission']) / 100,
                        2
                    );
                }
            }
            return [
                'tempcoms' => $tempcoms,
                'commissionDetail' => $commissionDetail
            ];
        } catch (\Exception $e) {
            $this->logger->info("Model_PaymentMethod updateCommissionData : ".$e->getMessage());
            return $commissionData;
        }
    }

    public function getSellerToken($sellerId)
    {
        try {
            if ($sellerId && $sellerId!=="" && $sellerId!==0) {
                $seller = $this->_customerRepository->getById($sellerId);
                
                if ($seller->getId() && !empty($seller->getCustomAttribute('seller_juno_recipient_token')->getValue())) {
                    return $seller->getCustomAttribute('seller_juno_recipient_token')->getValue();
                } else {
                    return false;
                }
                    
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->info("Model_PaymentMethod getSellerToken : ".$e->getMessage());
            return false;
        }
    }


    public function getQuoteId()
    {
        return $this->checkoutSession->getQuoteId();
    }
}
