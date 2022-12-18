<?php

namespace RZP\Models\Payment\PaymentMeta;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
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

    public function addGatewayAmountInformation(Payment\Entity $payment, int $gatewayAmount)
    {
        try
        {
            $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($payment->getId());

            if ($paymentMeta === null)
            {
                $paymentMeta = new PaymentMeta\Entity();

                $paymentMeta->setPaymentId($payment->getId());
            }

            $baseAmount = $payment->getBaseAmount();

            $paymentMeta->setGatewayAmount($gatewayAmount);

            $paymentMeta->setMismatchAmount(abs($baseAmount - $gatewayAmount));

            $mismatchReason = $gatewayAmount > $baseAmount ?
                MismatchAmountReason::CREDIT_SURPLUS : MismatchAmountReason::CREDIT_DEFICIT;

            $paymentMeta->setMismatchAmountReason($mismatchReason);

            $this->repo->saveOrFail($paymentMeta);

            return $paymentMeta->getId();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_META_AMOUNT_MISMATCH_ERROR
            );
        }
    }

    /**
     * This method is used to update DCC info if metadata already exists.
     * @param Entity $paymentMeta
     * @param $input
     * @return mixed
     */
    public function updateDccInfo(PaymentMeta\Entity $paymentMeta, $input)
    {
        $paymentMeta->setGatewayAmount($input['gateway_amount']);
        $paymentMeta->setGatewayCurrency($input['gateway_currency']);
        $paymentMeta->setForexRate($input['forex_rate']);
        $paymentMeta->setDccOffered($input['dcc_offered']);
        $paymentMeta->setDccMarkUpPercent($input['dcc_mark_up_percent']);

        $this->repo->saveOrFail($paymentMeta);

        return $paymentMeta->getId();
    }

    public function updateMccInfo(PaymentMeta\Entity $paymentMeta, $input)
    {
        $paymentMeta->setMccApplied($input['mcc_applied']);
        $paymentMeta->setMccForexRate($input['mcc_forex_rate']);
        $paymentMeta->setMccMarkDownPercent($input['mcc_mark_down_percent']);

        $this->repo->saveOrFail($paymentMeta);

        return $paymentMeta->getId();
    }

    public function edit(PaymentMeta\Entity $paymentMeta,$input)
    {
        $paymentMeta->edit($input);

        $this->repo->saveOrFail($paymentMeta);

        return $paymentMeta->getId();
    }
}
