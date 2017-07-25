<?php

namespace RZP\Models\Risk;

use RZP\Models\Base;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $risk = new Entity;

        $risk->build($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function edit(Entity $risk, array $input)
    {
        $risk->edit($input);

        return $this->repo->saveOrFail($risk);
    }

    public function get(string $id)
    {
        return $this->repo->risk->findOrFailPublic($id);
    }

    public function createRiskEntryOnMaxmindFailure(
        Payment\Entity $payment, int $riskScore)
    {
        $input = [
            Entity::MERCHANT_ID   => $payment->getMerchantId(),
            Entity::PAYMENT_ID    => $payment->getId(),
            Entity::FRAUD_TYPE    => Entity::SUSPECTED,
            Entity::MAXMIND_SCORE => $riskScore,
            Entity::SOURCE        => Entity::MAXMIND,
            Entity::COMMENTS      => ErrorCode::PAYMENT_SUSPECTED_FRAUD_BY_MAXMIND,
        ];

        $risk = $this->create($input);

        return $risk;
    }
}
