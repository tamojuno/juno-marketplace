<?php

namespace DigitalHub\Juno\Plugin\Sales\Model\Order\Payment\State;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\State\CommandInterface as BaseCommandInterface;

class AuthorizeCommandPlugin
{
    /**
     * @var \DigitalHub\Juno\Logger\Logger
     */
    private $logger;

    /**
     * @var \DigitalHub\Juno\Helper\Data
     */
    private $helper;

    public function __construct(
        \DigitalHub\Juno\Logger\Logger $logger,
        \DigitalHub\Juno\Helper\Data $helper
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Set pending order status on order place
     * see https://github.com/magento/magento2/issues/5860
     *
     * @param BaseCommandInterface $subject
     * @param \Closure $proceed
     * @param OrderPaymentInterface $payment
     * @param $amount
     * @param OrderInterface $order
     * @return mixed
     * @todo Refactor this when another option becomes available
     */
    public function aroundExecute(
        BaseCommandInterface $subject,
        \Closure $proceed,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        $result = $proceed($payment, $amount, $order);

        if ($payment->getMethod() === 'digitalhub_juno_creditcard') {

            // se pedido estiver sem captura automática habilitada, deixar como payment review
            if (!(int)$this->helper->getConfigData('digitalhub_juno_global/creditcard', 'capture')) {
                $orderStatus = Order::STATE_PAYMENT_REVIEW;

                if ($orderStatus && $order->getState() == Order::STATE_PROCESSING) {
                    $order->setState($orderStatus)
                        ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PAYMENT_REVIEW));
                }
            }
        }

        return $result;
    }
}
