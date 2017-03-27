<?php

namespace RZP\Gateway\Billdesk;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;

use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'billdesk';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'TxnReferenceNo'                => 'sometimes|max:50',
        'received'                      => 'sometimes|in:0,1',
        'AuthStatus' 					=> 'sometimes|max:5',
        'RefStatus' 	 				=> 'sometimes|max:5',
        'RefundId'                      => 'sometimes|string',
        'BankReferenceNo'               => 'sometimes|string',
    );

    public function findByGatewayRefundId($gatewayRefundId)
    {
        return $this->newQuery()
                    ->where('refundId', '=', $gatewayRefundId)
                    ->firstOrFail();
    }

    public function getSuccessfulRefundRecordForThePayment($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('ProcessStatus', '=', QueryStatus::Y)
                    ->where('RequestType', '=', '0410')
                    ->where('action', '=', Base\Action::REFUND)
                    ->where('received', '=', '1')
                    ->get();
    }

    public function findRefundByRefundId($refundId)
    {
        $refundEntities =  $this->newQuery()
                                ->where('refund_id', '=', $refundId)
                                ->whereNull('ErrorCode')
                                ->get();

        //
        // There should never be more than one successful gateway refund entity
        // for a given refund_id
        //

        if ($refundEntities->count() > 1)
        {
            throw new Exception\LogicException(
                'Multiple successful refund entities found for a refund ID',
                Error\ErrorCode::SERVER_ERROR_MULTIPLE_REFUNDS_FOUND,
                [
                    'refund_id' => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities;
    }

    public function fetchMissingBilldeskCancelledRefunds()
    {
        // SELECT *
        // FROM billdesk
        //     WHERE RefStatus = '0699'
        //       AND refund_id NOT IN
        //         (
        //             SELECT refunds.id
        //             FROM refunds
        //             JOIN payments ON refunds.payment_id = payments.id
        //             WHERE payments.gateway = 'billdesk'
        //               AND payments.transaction_id IS NOT NULL
        //         );

        $refundTable = Table::REFUND;
        $paymentTable = Table::PAYMENT;

        $billdeskAttributes = $this->getAttributeWithTableName('*');

        $billdeskRefundIdAttr = $this->getAttributeWithTableName('refund_id');

        $refundIdAttr = $this->manager->refund->getAttributeWithTableName(Payment\Refund\Entity::ID);
        $refundPaymentIdAttr = $this->manager->refund->getAttributeWithTableName(Payment\Refund\Entity::PAYMENT_ID);

        $paymentIdAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ID);
        $paymentGatewayAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::GATEWAY);
        $paymentTransactionIdAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::TRANSACTION_ID);

        $response = $this->newQuery()
                         ->select($billdeskAttributes)
                         ->where('RefStatus', '=', RefundStatus::CANCELLED)
                         ->whereNotIn(
                             $billdeskRefundIdAttr,
                             function($query)
                             use($refundIdAttr,
                                 $refundTable,
                                 $paymentTable,
                                 $refundPaymentIdAttr,
                                 $paymentIdAttr,
                                 $paymentGatewayAttr,
                                 $paymentTransactionIdAttr)
                             {
                                 $query->select($refundIdAttr)
                                       ->from($refundTable)
                                       ->join($paymentTable, $refundPaymentIdAttr, '=', $paymentIdAttr)
                                       ->where($paymentGatewayAttr, '=', Payment\Gateway::BILLDESK)
                                       ->whereNotNull($paymentTransactionIdAttr);
                             })
                         ->get();

        return $response;

    }
}
