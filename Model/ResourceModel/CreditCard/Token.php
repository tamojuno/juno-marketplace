<?php
namespace DigitalHub\Juno\Model\ResourceModel\CreditCard;

class Token extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('juno_creditcard_token', 'id');
    }
}
