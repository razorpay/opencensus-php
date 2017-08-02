<?php

namespace RZP\Models\Risk;

use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $risk = new Entity;

        $risk->build($input);

        $risk->associateRelatedEntites($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function edit(Entity $risk, array $input)
    {
        $risk->edit($input);

        $risk->associateRelatedEntites($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function logPaymentOnRiskFailure(
        Payment\Entity $payment, array $riskData)
    {
        $input = [
            Entity::MERCHANT_ID => $payment->getMerchantId(),
            Entity::PAYMENT_ID  => $payment->getId(),
        ];

        $input = array_merge($riskData, $input);

        return $this->create($input);
    }

    public function logPaymentOnMaxmindFailure(
        Payment\Entity $payment, string $riskScore)
    {
        $input = [
            Entity::MERCHANT_ID => $payment->getMerchantId(),
            Entity::PAYMENT_ID  => $payment->getId(),
            Entity::SOURCE      => Source::MAXMIND,
            Entity::RISK_SCORE  => $riskScore,
            Entity::REASON      => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
            Entity::FRAUD_TYPE  => Type::SUSPECTED,
        ];

        return $this->create($input);
    }
}
