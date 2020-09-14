<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\PaymentMeta;

use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    protected $paymentMeta = null;

    public function create($input)
    {
        $paymentMeta = (new PaymentMeta\Entity)->build($input);

        $this->$paymentMeta = $paymentMeta;

        $this->repo->saveOrFail($paymentMeta);

        return $paymentMeta;
    }


    public function addMetaInformation($payment, $input)
    {
        try
        {
            (new PaymentMeta\Validator)->validateInput('reference_id',$input);

            $input[PaymentMeta\Entity::PAYMENT_ID] = $payment->getId();

            $this->create($input);

            $this->trace->info(TraceCode::PAYMENT_META_REFERENCE, $input);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PAYMENT_META_REFERENCE_CREATION_ERROR
            );
        }
    }
}
