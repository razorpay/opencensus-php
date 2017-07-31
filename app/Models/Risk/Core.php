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
        $oldComment = $risk->getComments();

        // If not set, editRules validator will throw an exception
        if (empty($input[Entity::COMMENTS]) === false)
        {
            $newComment = $input[Entity::COMMENTS];
            $input[Entity::COMMENTS] = $oldComment + " || " + $newComment;
        }

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
            Entity::FRAUD_TYPE    => Type::SUSPECTED,
            Entity::RISK_SCORE    => $riskScore,
            Entity::SOURCE        => Source::MAXMIND,
            Entity::COMMENTS      => RiskCode::PAYMENT_SUSPECTED_FRAUD_BY_MAXMIND,
        ];

        $risk = $this->create($input);

        return $risk;
    }

    public function createRiskLogOnBlockedCard(Payment\Entity $payment)
    {
        $input = [
            Entity::MERCHANT_ID   => $payment->getMerchantId(),
            Entity::PAYMENT_ID    => $payment->getId(),
            Entity::FRAUD_TYPE    => Type::CONFIRMED,
            Entity::RISK_SCORE    => 0,
            Entity::SOURCE        => Source::INTERNAL,
            Entity::COMMENTS      => RiskCode::PAYMENT_FAILED_DUE_TO_BLOCKED_CARD,
        ];

        $risk = $this->create($input);

        return $risk;
    }
}
