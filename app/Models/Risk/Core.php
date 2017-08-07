<?php

namespace RZP\Models\Risk;

use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\FraudDetector;

class Core extends Base\Core
{
    use FraudDetector;

    public function create(array $input)
    {
        $risk = new Entity;

        $risk->build($input);

        $risk->associateRelatedEntities($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function edit(Entity $risk, array $input)
    {
        $risk->edit($input);

        $risk->associateRelatedEntities($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function get(string $id)
    {
        return $this->repo->risk->findOrFail($id);
    }

    /**
     * This function records riskScore by maxmind
     * when the maxmind accepts the payment but gateway/bank rejects it
     *
     * @param Payment\Entity $payment
     * @param array          $riskData
     *
     * @return Entity $risk
     */
    public function logPaymentOnGatewayRiskFailure(
        Payment\Entity $payment, array $riskData)
    {
        $input = [
            Entity::MERCHANT_ID => $payment->getMerchantId(),
            Entity::PAYMENT_ID  => $payment->getId(),
            Entity::RISK_SCORE  => $this->getRiskScore($payment),
        ];

        $input = array_merge($riskData, $input);

        return $this->create($input);
    }

    public function logPaymentForRiskManual(
        Payment\Entity $payment, array $input)
    {
        $risk = $this->repo->risk->fetchByPaymentId($payment->getId());

        // If a payment is tagged as confirmed fraud, add its maxmind score
        $input[Entity::RISK_SCORE] = $this->getRiskScore($payment);
        $input[Entity::MERCHANT_ID] = $payment->getMerchantId();

        if ($risk === null)
        {
            return $this->create($input);
        }

        return $this->edit($risk, $input);
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

    public function logPaymentOnBlockedCard(
        Payment\Entity $payment)
    {
        $riskScore = $this->getRiskScore($payment);

        $input = [
            Entity::MERCHANT_ID => $payment->getMerchantId(),
            Entity::PAYMENT_ID  => $payment->getId(),
            Entity::SOURCE      => Source::INTERNAL,
            Entity::RISK_SCORE  => $riskScore,
            Entity::REASON      => RiskCode::PAYMENT_FAILED_DUE_TO_BLOCKED_CARD,
            Entity::FRAUD_TYPE  => Type::CONFIRMED,
        ];

        return $this->create($input);
    }
}
