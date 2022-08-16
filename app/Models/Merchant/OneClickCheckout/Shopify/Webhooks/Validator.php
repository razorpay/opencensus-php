<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify\Webhooks;

use App;
use Throwable;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Merchant\OneClickCheckout\Shopify;

class Validator extends Base\Core
{

    protected $monitoring;

    public function __construct()
    {
        parent::__construct();
        $this->monitoring = new Shopify\Monitoring();
    }

    /**
     * Verify integrity of the webhooks from Shopify.
     * @return bool
     */
    public function isSignatureValid(string $input, string $signature, string $secret): bool
    {
        $calculatedSignature = base64_encode(hash_hmac('sha256', $input, $secret, true));
        $isValid = hash_equals($signature, $calculatedSignature);
        if ($isValid === false)
        {
            $this->trace->error(
              TraceCode::SHOPIFY_1CC_WEBHOOK_VALIDATION_FAILED,
              [
                'type'                 => 'invalid_signature',
                'signature'            => $signature,
                'calculated_signature' => $calculatedSignature,
              ]);
            return false;
        }
        return true;
    }

    // $merchantRzpOrderId - Rzp order id got from Shopify against a transaction
    public function validateOrderAndPayment(
        Order\Entity $order,
        Payment\Entity $payment,
        Merchant\Entity $merchant,
        string $merchantRzpOrderId): bool
    {
        if ($order->is1ccShopifyOrder() === false)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
                [
                    'type'       => 'non_1cc_order',
                    'order_id'   => $order->getPublicId(),
                    'payment_id' => $payment->getPublicId(),
                ]);
            return false;
        }

        if ($merchantRzpOrderId !== $order->getPublicId())
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
                [
                    'type'                 => 'mismatch_order_id',
                    'order_id'             => $order->getPublicId(),
                    'payment_id'           => $payment->getPublicId(),
                    'merchant_rzp_orderId' => $merchantRzpOrderId,
                ]);
            return false;
        }

        $canIssueRefund = $this->canIssueRefund($payment);
        if ($canIssueRefund === false)
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
                [
                    'type'       => 'refund_not_applicable',
                    'order_id'   => $order->getPublicId(),
                    'payment_id' => $payment->getPublicId(),
                ]);
            return false;
        }

        if ($merchant->getPublicId() !== $order->getMerchantId())
        {
            $this->trace->error(
                TraceCode::SHOPIFY_1CC_WEBHOOK_ISSUE_REFUND_VALIDATION_FAILED,
                [
                    'type'        => 'mismatch_merchant_id',
                    'order_id'    => $order->getPublicId(),
                    'payment_id'  => $payment->getPublicId(),
                    'merchant_id' => $merchant->getPublicId(),
                ]);
            return false;
        }

        return true;
    }

    /**
     * Ideally status check should be enough and no method check is needed
     * but we add it to be explicit
     * NOTE: partial refunds not supported
     * @param Payment\Entity $payment
     * @return bool if a full refund can be issued
     */
    protected function canIssueRefund(Payment\Entity $payment): bool
    {
        return $payment->getStatus() === 'captured' and $payment->getRefundStatus() === null and $payment->getMethod() !== 'cod';
    }

}
