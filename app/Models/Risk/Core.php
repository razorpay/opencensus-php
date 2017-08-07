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

    public function logPaymentForSource(
        Payment\Entity $payment, string $source, array $data)
    {
        $func = 'logPaymentFor' . ucwords($source);

        if (method_exists($this, $func) === true)
        {
            return $this->{$func}($payment, $data);
        }
    }

    protected function logPaymentForGateway(
        Payment\Entity $payment, array $riskData)
    {
        return $this->create($payment, $riskData);
    }

    protected function logPaymentForManual(
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

    protected function logPaymentForMaxmind(
        Payment\Entity $payment, array $data)
    {
        $input = [
            Entity::SOURCE      => Source::MAXMIND,
            Entity::REASON      => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
            Entity::FRAUD_TYPE  => Type::SUSPECTED,
            Entity::RISK_SCORE  => $data[Entity::RISK_SCORE],
        ];

        return $this->create($payment, $input);
    }

    protected function logPaymentForInternal(
        Payment\Entity $payment, array $data)
    {
        $input = [
            Entity::SOURCE      => Source::INTERNAL,
            Entity::REASON      => RiskCode::PAYMENT_FAILED_DUE_TO_BLOCKED_CARD,
            Entity::FRAUD_TYPE  => Type::CONFIRMED,
        ];

        return $this->create($payment, $input);
    }
}
