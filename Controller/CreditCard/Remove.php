<?php

namespace DigitalHub\Juno\Controller\CreditCard;

use DigitalHub\Juno\Model\CreditCard\TokenFactory;
use Magento\Framework\App\Action\Context;

class Remove extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $pageFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;
    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \DigitalHub\Juno\Model\CreditCard\TokenFactory $tokenFactory,
        \Magento\Customer\Model\Session $customerSession,
        Context $context
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->customerSession = $customerSession;
        $this->tokenFactory = $tokenFactory;
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->_redirect('/');
        }

        $token = $this->tokenFactory->create()->load((int)$this->getRequest()->getParam('id'));
        if ($token->getCustomerId() != $this->customerSession->getCustomerId()) {
            return $this->_redirect('/');
        }

        try {
            $token->delete();
            $this->messageManager->addSuccessMessage('O cartão de crédito salvo foi removido');
        } catch (\Exception $e) {
            $this->messageManager
                ->addErrorMessage(
                    sprintf(
                        '%s%s',
                        'Ocorreu um erro ao tentar remover o cartão de crédito salvo.',
                        ' Por favor, tente novamente mais tarde.'
                    )
                );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
