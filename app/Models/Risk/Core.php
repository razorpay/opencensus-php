<?php

namespace RZP\Models\Risk;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(Payment\Entity $payment, array $input)
    {
        $risk = new Entity;

        // Validator expects publicId
        $input[Entity::PAYMENT_ID] = $payment->getPublicId();

        $risk->build($input);

        $risk->payment()->associate($payment);

        $risk->merchant()->associate($payment->merchant);

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
        Payment\Entity $payment,
        string $source,
        array $data = [])
    {
        try
        {
            if (Source::isValidSource($source) === true)
            {
                $data[Entity::SOURCE] = $source;
                return $this->create($payment, $data);
            }

            throw new Exception\LogicException(
                "Risk Action - $func not found",
                ErrorCode::SERVER_ERROR_MISSING_HANDLER, $data
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::SERVER_ERROR_LOG_RISK,
                [
                    'data'          => $data,
                    'payment_id'    => $payment->getId(),
                    'source'        => $source
                ]);

            return null;
        }
    }
}
