<?php

namespace RZP\Models\Partner\Commission;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Metric;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Processor\Refund as RefundTrait;
use RZP\Jobs\CommissionCapture;

class RefundCommission extends Core
{
    protected   Payment\Entity $refundedPayment;
    protected   string         $refundId;
    protected   string         $paymentId;
    protected   int            $refundAmount;
    private     Entity         $refundCommission;

    public function __construct(string $refundId, string $paymentId, string $refundAmount)
    {
        parent::__construct();

        $this->refundId     = $refundId;
        $this->paymentId    = $paymentId;
        $this->refundAmount = $refundAmount;
    }

    public function createReversalCommissionForRefund(): void
    {
        $this->refundedPayment = $this->repo->payment->find($this->paymentId);
        $commission = $this->repo->commission->findBySourceIdAndCommissionType($this->refundedPayment->getId());

        $this->refundCommission = $this->buildRefundCommission($commission);

        $this->repo->transaction(function ()
        {
                $this->repo->save($this->refundCommission);

                $this->trace->info(TraceCode::COMMISSION_REFUND_SAVED, ['commission_id' => $this->refundCommission->getId()]);

                $this->trace->count(Metric::COMMISSION_CREATED_TOTAL, $this->refundCommission->getMetricDimensions());

                // send to queue to create transaction and update balance of partner
                CommissionCapture::dispatch($this->mode, $this->refundCommission->getPublicId())->afterCommit();
        });
    }

    protected function buildRefundCommission(Entity $commission): Entity
    {
        $refundrate = $this->refundAmount / $this->refundedPayment->getAmount() ;

        $payload = [
            Entity::FEE         => (int) ($refundrate * $commission->getFee()),
            Entity::TAX         => (int) ($refundrate * $commission->getTax()),
            Entity::TYPE        => $commission->getType(),
            Entity::MODEL       => $commission->getModel(),
            Entity::DEBIT       => (int) ($refundrate * $commission->getCredit()),
            Entity::CREDIT      => 0,
            Entity::RECORD_ONLY => (int) $commission->getRecordOnly(),
            Entity::CURRENCY    => $commission->getCurrency(),
            Entity::SOURCE_ID   => $this->refundId,
            Entity::SOURCE_TYPE => Constants::REFUND,
        ];

        // $refundEntity = $this->createVirtualRefundEntity($this->refundedPayment,[RefundEntity::ID => $this->refundId, RefundEntity::AMOUNT => $this->refundAmount]);
        // we can create a virtual refund entity and pass it to build method but when
        // the refunds are not created in monolith and shifted to scrooge this will
        // throw exception. So we are passing null as refund entity and setting source type and id
        return $this->build(
            null,
            $commission->partner,
            $commission->partnerConfig,
            $payload
        );
    }
}
