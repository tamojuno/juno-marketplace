define(
    [
        'ko',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'DigitalHub_Juno/js/action/get-creditcard-saved',
        'DigitalHub_Juno/js/action/get-creditcard-installments',
        'mage/translate',
        'jquery',
    ],
    function (
        ko,
        _,
        Component,
        checkoutData,
        quote,
        priceUtils,
        fullScreenLoader,
        additionalValidators,
        creditCardData,
        savedCardsAction,
        installmentsAction,
        $t,
        $
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'DigitalHub_Juno/payment/creditcard',
                isLoggedIn: false,
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardNumber: '',
                creditCardHolderName: '',
                creditCardVerificationNumber: '',
                creditCardHash: '',
                creditCardInstallments: 1,
                availableInstallments: [],
                canSaveCc: false,
                saveCc: false,
                savedCcId: null,
                savedCcLast: null
            },

            validate: function() {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'isLoggedIn',
                        'creditCardExpYear',
                        'creditCardExpMonth',
                        'creditCardNumber',
                        'creditCardHolderName',
                        'creditCardVerificationNumber',
                        'creditCardHash',
                        'creditCardInstallments',
                        'maskedCreditCardNumber',
                        'canSaveCc',
                        'saveCc',
                        'savedCcId',
                        'savedCcLast'
                    ]);

                this.savedCards = ko.observableArray();
                this.availableInstallments = ko.observableArray();
                return this;
            },

            initialize: function() {
                var self = this;
                this._super();

                $.when(installmentsAction()).then(function(result){
                    self.availableInstallments(result);
                });

                $.when(savedCardsAction()).then(function(result){
                    self.savedCards(result);
                });

                if(window.checkoutConfig.quoteData.customer_id){
                    this.isLoggedIn(true);
                }

                this.canSaveCc(this.getMethodConfig().can_save_cc && window.checkoutConfig.quoteData.customer_id);
            },

            getSavedCcLast: function(){
                for(var i = 0; i < this.savedCards().length; i++){
                    var item = this.savedCards()[i];
                    if(item.value == this.savedCcId()){
                        return item.cc_last;
                    }
                }
            },

            getData: function() {
                return {
                    method: this.getCode(),
                    additional_data: {
                        'hash': this.creditCardHash(),
                        'cc_last': !this.savedCcId() ? this.creditCardNumber().substr(-4) : this.getSavedCcLast(),
                        'installments': this.creditCardInstallments(),
                        'save_cc': this.saveCc(),
                        'saved_cc_id': this.savedCcId()
                    }
                };
            },

            isActive: function () {
                return true;
            },

            getCcMonths: function() {
                var months = [];
                for(var i = 1; i<=12; i++){
                    months.push({key: (i < 10 ? '0' + i : i), label: (i < 10 ? '0' + i : i)});
                }
                return months;
            },

            getCcYears: function() {
                var years = [];
                var date = new Date();
                var max_year = parseInt(date.getFullYear()) + 20;
                for(var i = parseInt(date.getFullYear()); i<=max_year; i++){
                    years.push({key: i, label: i});
                }
                return years;
            },

            getInstallments: function(){
                return this.availableInstallments()
            },

            getGlobalConfig: function() {
                return window.checkoutConfig.payment.digitalhub_juno_global
            },

            getMethodConfig: function() {
                return window.checkoutConfig.payment.digitalhub_juno_creditcard
            },

            _createToken: function(callback){
                var isProduction = !this.getGlobalConfig().sandbox;
                var self = this;

                var checkout = new DirectCheckout(self.getGlobalConfig().public_token, isProduction);
                var cardData = {
                    cardNumber: self.creditCardNumber(),
                    holderName: self.creditCardHolderName(),
                    securityCode: self.creditCardVerificationNumber(),
                    expirationMonth: self.creditCardExpMonth(),
                    expirationYear: self.creditCardExpYear()
                };

                checkout.getCardHash(cardData, function(result){
                    callback(result)
                }, function(error){
                    callback(null, error);
                })
            },

            validateCreditCardData: function(callback){
                var has_errors = false;

                if(!this.savedCcId()){
                    var isProduction = !this.getGlobalConfig().sandbox;
                    var self = this;

                    var checkout = new DirectCheckout(self.getGlobalConfig().public_token, isProduction);
                    var cardData = {
                        cardNumber: self.creditCardNumber(),
                        holderName: self.creditCardHolderName(),
                        securityCode: self.creditCardVerificationNumber(),
                        expirationMonth: self.creditCardExpMonth(),
                        expirationYear: self.creditCardExpYear()
                    };

                    if(!checkout.isValidCardNumber(cardData.cardNumber)){
                        has_errors = true;
                    }

                    if(!checkout.isValidSecurityCode(cardData.cardNumber, cardData.securityCode)){
                        has_errors = true;
                    }

                    if(!checkout.isValidExpireDate(cardData.expirationMonth, cardData.expirationYear)){
                        has_errors = true;
                    }
                }

                callback(!has_errors);
            },

            beforePlaceOrder: function(){
                var _this = this;

                // validate default form
                if(this.validate()){
                    this.validateCreditCardData(function(isValid){
                        if(isValid){
                            fullScreenLoader.startLoader();

                            if(_this.savedCcId()){
                                _this.placeOrder();
                            } else {
                                _this._createToken(function(cardHash){

                                    if (cardHash) {
                                        _this.creditCardHash(cardHash);
                                        _this.placeOrder();
                                    } else {
                                        _this.creditCardHash = null;
                                        _this.messageContainer.addErrorMessage({message: $t('Token generation error. Please contact support.')});
                                    }

                                    fullScreenLoader.stopLoader();
                                })
                            }
                        } else {
                            _this.messageContainer.addErrorMessage({message: $t('Dados de cartão inválidos')});
                        }
                    });
                }
            }
        });
    }
);
