<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{

    public function createLog(Payment\Entity $payment)
    {
        $paymentAnalytics = (new Core)->create($payment);

        return $paymentAnalytics->toArrayPublic();
    }

    public function getAuditsForPayment($id)
    {
        $audits = $this->repo->payment_analytics->findForPayment($id);

        return $audits->toArrayPublic();
    }

    /**
     * A payment made over private auth is normally not up to Razorpay at all,
     * since the merchant is free to send whatever data he wants. However, it's
     * possible that he's using a Razorpay server side integration, and in that
     * case we may take care to send the library metadata field ourselves.
     * Failing this, if the metadata array is blank, we still classify this as S2S.
     *
     * @param  array $input
     */
    public function setMetadataForS2SPayment(array & $input)
    {
        $input['_'] = $input['_'] ?? [];

        if (isset($input['_'][Entity::LIBRARY]) === false)
        {
            $input['_'][Entity::LIBRARY] = Metadata::S2S;
        }
    }

    /**
     * If a payment is being made over public auth, then it's most likely coming
     * via one of our integrations, i.e. checkout.js, razorpay.js, or Android.
     * All of these integrations set the library metadata field to inform us of
     * the integration used. If the library field isn't set, that could mean one
     * of two things:
     *
     * - The merchant is hitting a public route directly, i.e. without using a
     * Razorpay-provider integration. This is classified as a direct integration.
     *
     * - The merchant is using an extremely old version of an integration. We
     * don't have a great way to solve for this, so we're just going to call
     * this direct too.
     *
     * @param  array $input
     */
    public function setMetadataForPublicAuthPayment(array & $input)
    {
        if (isset($input['_'][Entity::LIBRARY]) === false)
        {
            $input['_'][Entity::LIBRARY] = Metadata::DIRECT;
        }
    }

    /**
     * If a payment is being made over app auth, then it's coming not from the
     * merchant, but directly via a bank side integration. This is applicable for
     * push payments, like bank transfers (for virtual accounts) and BharatQR.
     *
     * @param  array $input
     */
    public function setMetadataForAppAuthPayment(array & $input)
    {
        if (isset($input['_'][Entity::LIBRARY]) === false)
        {
            $input['_'][Entity::LIBRARY] = Metadata::PUSH;
        }
    }

    public function updatePaymentAnalyticsData(Payment\Entity $payment)
    {
        try
        {
            $pa = $payment->analytics;

            if ($pa === null)
            {
                $pa = new Payment\Analytics\Entity();
            }

            $parser = new Parser;

            $parser->recordPaymentRequestData($pa, $payment);

            $this->repo->saveOrFail($pa);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_ERROR_SAVE_ANALYTICS_DATA);
        }
    }

    public function createPaymentAnalyticsPartition()
    {
        try
        {
            $this->repo->payment_analytics->managePartitions();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::TABLE_PARTITION_ERROR);

            return ['success' => false];
        }

        return ['success' => true];
    }
}
