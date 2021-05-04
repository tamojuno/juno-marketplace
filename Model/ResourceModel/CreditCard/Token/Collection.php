<?php
namespace DigitalHub\Juno\Model\ResourceModel\CreditCard\Token;

use DigitalHub\Juno\Model\CreditCard\Token as Model;
use DigitalHub\Juno\Model\ResourceModel\CreditCard\Token as ResourceModel;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'juno_creditcard_token_collection';
    protected $_eventObject = 'juno_creditcard_token_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
