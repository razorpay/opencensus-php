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
        Entity::BANK                    => 'sometimes|min:4|max:4',
        Entity::GATEWAY_PAYMENT_ID      => 'sometimes|string|max:50',
        Entity::NPCI_REFERENCE_ID       => 'sometimes|string|max:20',
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        Entity::REFUND_ID               => 'sometimes|string|min:14|max:18',
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
        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentStatus = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $upiId = $this->dbColumn(Entity::ID);
        $upiVpa = $this->dbColumn(Entity::VPA);
        $upiBank = $this->dbColumn(Entity::BANK);
        $upiPaymentId = $this->dbColumn(Entity::PAYMENT_ID);

        return $this->newQuery()
                    ->select($upiId, $upiVpa)
                    ->join(TABLE::PAYMENT, $upiPaymentId, '=', $paymentId)
                    ->where($paymentStatus, '=', Payment\Status::CAPTURED)
                    ->where($upiId, '>', $lastId)
                    ->whereNull($upiBank)
                    ->limit($limit)
                    ->orderBy($upiId)
                    ->get();
    }

}
