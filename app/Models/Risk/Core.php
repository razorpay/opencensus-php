<?php

namespace RZP\Models\Risk;

use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function create(Payment\Entity $payment, array $input)
    {
        $risk = new Entity;

        // Validator expects publicId
        $input[Entity::PAYMENT_ID] = $payment->getPublicId();

        $risk->build($input);

        $risk->payment()->associate($payment);
        $risk->merchant()->associate($payment->getMerchantId());

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function edit(Entity $risk, array $input)
    {
        $risk->edit($input);

        // If the source exists, validate it is manual
        $risk->getValidator()->validateSourceManual($input[Entity::SOURCE]);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    /**
     * Tag payments as risky if gateway throws an error
     *
     *
     * @param Payment\Entity $payment
     * @param array          $riskData
     *
     * @return Entity $risk
     */
    public function logPaymentOnGatewayRiskFailure(
        Payment\Entity $payment, array $riskData)
    {
        return $this->create($payment, $riskData);
    }

    public function logPaymentForRiskManual(
        Payment\Entity $payment, array $input)
    {
        $risk = $this->repo->risk->fetchByPaymentId($payment->getId());

        if ($risk === null)
        {
            return $this->create($payment, $input);
        }

        // We allow edits only if the source is manual
        return $this->edit($risk, $input);
    }

    public function logPaymentOnMaxmindFailure(
        Payment\Entity $payment, string $riskScore)
    {
        $input = [
            Entity::SOURCE      => Source::MAXMIND,
            Entity::REASON      => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
            Entity::FRAUD_TYPE  => Type::SUSPECTED,
            Entity::RISK_SCORE  => $riskScore,
        ];

        return $this->create($payment, $input);
    }

    public function logPaymentOnBlockedCard(
        Payment\Entity $payment)
    {
        $input = [
            Entity::SOURCE      => Source::INTERNAL,
            Entity::REASON      => RiskCode::PAYMENT_FAILED_DUE_TO_BLOCKED_CARD,
            Entity::FRAUD_TYPE  => Type::CONFIRMED,
        ];

        return $this->create($payment, $input);
    }
}
