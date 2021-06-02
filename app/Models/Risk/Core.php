<?php

namespace RZP\Models\Risk;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Jobs\NotifyRas;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function create(Payment\Entity $payment, array $input)
    {
        $risk = new Entity;

        // Validator expects publicId
        $input[Entity::PAYMENT_ID] = $payment->getPublicId();

        if (isset($input[Entity::RISK_SCORE]) === false)
        {
            $input[Entity::RISK_SCORE] = -1;
        }

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

    public function postCustomerFlaggingToRiskService(array $input, array $entityDetails)
    {
        try
        {
            $customerFlaggingInput = $this->getCustomerFlaggingInput($input, $entityDetails);

            NotifyRas::dispatch($this->mode, $customerFlaggingInput);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, null, ['input' => $input, 'entity_details' => $entityDetails]);
        }

        return ['status' => 'done'];
    }

    protected function getCustomerFlaggingInput(array $input, array $entityDetails)
    {
        $merchantId = $entityDetails['merchant_id'];

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $merchantAppsExemptFromRiskCheck = $merchant->isFeatureEnabled(Feature\Constants::APPS_EXTEMPT_RISK_CHECK);

        return [
            'merchant_id'     => $entityDetails['merchant_id'],
            'entity_type'     => $entityDetails['entity'],
            'entity_id'       => Entity::stripDefaultSign($entityDetails['entity_id']),
            'category'        => 'customer_flag',
            'source'          => $input['source'],
            'data'            => [
                'email_id'               => $input['email_id'],
                'contact_no'             => $input['contact_no'] ?? "",
                'name'                   => $input['name'] ?? "",
                'comments'               => $input['comments'] ?? "",
                'apps_exempt_risk_check' => ($merchantAppsExemptFromRiskCheck === true ? '1' : '0'),
            ],
            'event_timestamp' => (string) Carbon::now()->getTimestamp(),
            'event_type'      => 'report_fraud',
        ];
    }
}
