<?php

namespace RZP\Models\Risk;

use RZP\Exception;
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
        //
        // If the risk entity is already marked as confirmed,
        // Do not allow edits
        //
        if ($risk->getFraudType() == Type::CONFIRMED)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot edit confirmed risk entities',
                'risk_id',
                ['risk_id' => $risk->getPublicId()]);
        }

        $risk->edit($input);

        $this->repo->saveOrFail($risk);

        return $risk;
    }

    public function logPaymentForSource(
        Payment\Entity $payment, string $source, array $data = [])
    {
        $func = 'logPaymentFor' . studly_case($source);

        if (method_exists($this, $func) === true)
        {
            return $this->{$func}($payment, $data);
        }

        $error = "Risk Action - $func not found";

        $data[Entity::PAYMENT_ID] = $payment->getId();
        $data[Entity::SOURCE] = $source;

        throw new Exception\LogicException(
            $error, ErrorCode::SERVER_ERROR_MISSING_HANDLER, $data);
    }

    protected function logPaymentForGateway(
        Payment\Entity $payment, array $data)
    {
        // Source is present as part of the data

        return $this->create($payment, $data);
    }

    protected function logPaymentForBank(
        Payment\Entity $payment, array $data)
    {
        // Source is present as part of the data

        return $this->create($payment, $data);
    }

    protected function logPaymentForManual(
        Payment\Entity $payment, array $data)
    {
        $data[Entity::SOURCE] = Source::MANUAL;

        return $this->create($payment, $data);
    }

    protected function logPaymentForMaxmind(
        Payment\Entity $payment, array $data)
    {
        $data[Entity::SOURCE] = Source::MAXMIND;

        return $this->create($payment, $data);
    }

    protected function logPaymentForInternal(
        Payment\Entity $payment, array $data)
    {
        $data[Entity::SOURCE] = Source::INTERNAL;

        return $this->create($payment, $data);
    }
}
