<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Constants\Table;
use RZP\Models\Payment;

class Repository extends Base\Repository
{
    protected $entity = 'upi';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID    => 'sometimes|string|min:14|max:18',
        Entity::BANK          => 'sometimes|in:icici'
    );

    public function fetchGatewayPaymentIdByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id' , '=', $paymentId)
                    ->pluck('gateway_payment_id');
    }

    public function fetchByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id' , '=', $paymentId)
                    ->first();
    }

    public function fetchAllForBankUpdate($limit = 100, $lastId = 0)
    {
        $paymentId = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ID);
        $paymentStatus = $this->manager->payment->getAttributeWithTableName(Payment\Entity::STATUS);

        $upiId = $this->getAttributeWithTableName(Entity::ID);
        $upiVpa = $this->getAttributeWithTableName(Entity::VPA);
        $upiBank = $this->getAttributeWithTableName(Entity::BANK);
        $upiPaymentId = $this->getAttributeWithTableName(Entity::PAYMENT_ID);

        return $this->newQuery()
                    ->select($upiId, $upiVpa)
                    ->join(TABLE::PAYMENT, $upiPaymentId, '=', $paymentId)
                    ->where($paymentStatus, '=', Payment\Status::CAPTURED)
                    ->where($upiId, '>', $lastId)
                    ->whereNull($upiBank)
                    ->limit($limit)
                    ->get();
    }

}
