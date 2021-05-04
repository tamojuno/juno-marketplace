<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   DigitalHub_Juno
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace DigitalHub\Juno\Controller\Seller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;

class SaveConfig extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var $_customerRepository
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Customer\Model\Customer\Mapper
     */
    protected $_customerMapper;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $_customerDataFactory;

    /**
     * @var DataObjectHelper
     */
    protected $_dataObjectHelper;

    /**
     * @var UrlFactory
     */
    protected $_urlFactory;

    /**
     * @param Context $context
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Magento\Customer\Model\Customer\MapperFactory $customerMapper
     * @param \Magento\Customer\Model\SessionFactory $customerSession
     * @param \Magento\Customer\Model\UrlFactory $urlFactory
     */
    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Magento\Customer\Model\Customer\MapperFactory $customerMapper,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Customer\Model\UrlFactory $urlFactory
    ) {
        $this->_customerSession = $customerSession;
        $this->_customerRepository = $customerRepository;
        $this->_customerMapper = $customerMapper;
        $this->_customerDataFactory = $customerDataFactory;
        $this->_dataObjectHelper = $dataObjectHelper;
        $this->_urlFactory = $urlFactory;
        parent::__construct($context);
    }

    /**
     * Retrieve customer session object.
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession->create();
    }

    /**
     * Check customer authentication.
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_urlFactory
            ->create()
            ->getLoginUrl();

        if (!$this->_customerSession->create()->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Save Seller's gst configuration Data.
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if ($this->getRequest()->isPost()) {
            $customerData = $this->getRequest()->getParams();
            $customerId = $this->_getSession()->getCustomerId();
            $savedData = $this->_customerRepository->getById($customerId);
            $customer = $this->_customerDataFactory->create();
            $customerData = array_merge(
                $this->_customerMapper->create()->toFlatArray($savedData),
                $customerData
            );
            $customerData['id'] = $customerId;
            $this->_dataObjectHelper->populateWithArray(
                $customer,
                $customerData,
                \Magento\Customer\Api\Data\CustomerInterface::class
            );
            try {
                $this->_customerRepository->save($customer);
                $this->messageManager->addSuccess(__('Configuration saved successfully.'));
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception(__('Can not save the records.'));
            }
        }
        return $this->resultRedirectFactory->create()
            ->setPath(
                '*/*/config'
            );
    }
}
