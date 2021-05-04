<?php
namespace DigitalHub\Juno\Logger;

class Logger extends \Monolog\Logger
{
    public function addRecord($level, $message, array $context = [])
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $junoHelper = $objectManager->create(\DigitalHub\Juno\Helper\Data::class);

        if ((int)$junoHelper->getConfigData('digitalhub_juno_global', 'debug')) {
            return parent::addRecord($level, $message, $context);
        }
        return true;
    }
}
