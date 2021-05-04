<?php
namespace DigitalHub\Juno\Model\CreditCard;

class Token extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'digitalhub_juno_creditcard_token';
    protected $_cacheTag = 'digitalhub_juno_creditcard_token';
    protected $_eventPrefix = 'digitalhub_juno_creditcard_token';

    protected function _construct()
    {
        $this->_init(\DigitalHub\Juno\Model\ResourceModel\CreditCard\Token::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
