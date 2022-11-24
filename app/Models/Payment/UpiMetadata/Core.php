<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Payment\Entity $payment): Entity
    {
        $upiMetadata = (new Entity)->build($input);

        $upiMetadata->associatePayment($payment);

        return $upiMetadata;
    }

    public function update(Entity $metadata): Entity
    {
        $dirty = $metadata->getDirty();
        $original = $metadata->getRawOriginal();

        $toTrace = [
            'payment_id'            => $metadata->getPaymentId(),
            'type'                  => $metadata->getType(),
            'old_internal_status'   => $original[Entity::INTERNAL_STATUS] ?? null,
            'new_internal_status'   => $dirty[Entity::INTERNAL_STATUS] ?? null,
            'old_remind_at'         => $original[Entity::REMIND_AT] ?? null,
            'new_remind_at'         => $dirty[Entity::REMIND_AT] ?? null,
            'old_vpa'               => $original[Entity::VPA] ?? null,
            'new_vpa'               => $dirty[Entity::VPA] ?? null,
        ];

        $this->repo->saveOrFail($metadata);

        $this->trace->info(TraceCode::PAYMENT_UPI_METADATA_UPDATED, $toTrace);

        return $metadata;
    }
}
