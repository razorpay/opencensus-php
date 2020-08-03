<?php

namespace RZP\Gateway\Upi\Base;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\UpiMandate;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Exception\BaseException;
use RZP\Exception\GatewayErrorException;

trait RecurringTrait
{
    protected $gatewayDataIdToActionMap = [
        Action::AUTHENTICATE    => 'create',
        Action::AUTHORIZE       => 'execte',
        Action::DEBIT           => 'execte',
        Action::MANDATE_CANCEL  => 'revoke',
        Action::PRE_DEBIT       => 'notify',
    ];

    // 24 hours in second
    protected $defaultExecuteBuffer = 86400;

    protected function getGatewayDataBlockForUpiRecurring($input, $action)
    {
        $attempt = 0;

        $action = $this->gatewayDataIdToActionMap[$action];

        $id = $input['payment']['id'] . $action . $attempt;

        $gatewayData = [
            'id'     => $id,
            'act'    => $action,
            'ano'    => $attempt,
        ];

        return $gatewayData;
    }

    protected function setGatewayDataBlockForUpiRecurring(array & $input)
    {
        $input['upi']['gateway_data'] = $this->getGatewayDataBlockForUpiRecurring($input, $this->action);
    }

    protected function setRequestDataForUpiRecurring(array & $input, Entity $upi)
    {
        // We need to add api action, just for tracking purposes
        $input[Constants::UPI][Constants::ACTION] = $this->getAction();

        $gatewayData    = $upi->getGatewayData();
        $action         = $gatewayData[Constants::ACTION];
        $attempt        = $gatewayData[Constants::ATTEMPT];

        $id = $input[Entity::PAYMENT][Entity::ID] . $action . $attempt;
        $gatewayData[Constants::ID] = $id;

        // First set the correct gateway data
        $input[Constants::UPI][Entity::GATEWAY_DATA] = $gatewayData;

        // Since Gateway Upi entity has VPA taken, UPI in input does.
        // Need to fix that too
        $input[Constants::UPI][Entity::VPA] = $upi->getVpa();
    }

    protected function isFirstRecurringPayment(array $input): bool
    {
        return (($input[Entity::PAYMENT][Payment\Entity::RECURRING] === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL));
    }

    protected function isSecondRecurringPayment(array $input): bool
    {
        return (($input[Entity::PAYMENT][Payment\Entity::RECURRING] === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::AUTO));
    }

    protected function authorizeRecurring(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->authorizeRecurring($input);
    }

    protected function recurringMandateCreateCallback(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->callback($input);
    }

    protected function recurringMandateRevoke(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->mandateRevoke($input);
    }

    protected function firstDebit(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->debit($input);
    }

    protected function sendDebitRequest(array $input, Entity $upi)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        $response = $gateway->debit($input);

        $attributes = array_only($response['data'], (new Entity)->getFillable());

        $this->updateGatewayPaymentResponse($upi, $attributes, false);

        if ($response['success'] !==  true)
        {
            $exception = new GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                null,
                null,
                $this->action);

            $remindAt = $this->getNextRemindAtForRecurring($upi, $response, $exception);

            $exception->setData($this->getResponseForAutoRecurring($input, $remindAt, $upi));

            throw $exception;
        }

        $remindAt = $this->getNextRemindAtForRecurring($upi, $response);

        return $this->getResponseForAutoRecurring($input, $remindAt, $upi);
    }

    protected function sendPreDebitRequest(array $input, Entity $upi)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        $response = $gateway->preDebit($input);

        $attributes = array_only($response['data'], (new Entity)->getFillable());

        $this->updateGatewayPaymentResponse($upi, $attributes, false);

        if ($response['success'] !==  true)
        {
            $exception = new GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                null,
                null,
                $this->action);

            $remindAt = $this->getNextRemindAtForRecurring($upi, $response, $exception);

            $exception->setData($this->getResponseForAutoRecurring($input, $remindAt, $upi));

            throw $exception;
        }

        $remindAt = $this->getNextRemindAtForRecurring($upi, $response);

        return $this->getResponseForAutoRecurring($input, $remindAt, $upi);
    }

    protected function isFirstUpiRecurringPayment($payment): bool
    {
        return ($payment['method'] === 'upi' and $payment['recurring_type'] === 'initial');
    }

    /**
     * @param array $input
     * @return Entity
     */
    protected function firstOrCreateEntityForRecurring(array $input, string $action, bool $countAttempt = false)
    {
        $entity = $this->getUpiEntityForAction($input, $action);

        if (($entity instanceof Entity) === true)
        {
            if ($countAttempt === true)
            {
                $gatewayData    = $entity->getGatewayData();

                // If the entities do not have the attempt
                $newAttempt     = ($gatewayData[Constants::ATTEMPT] ?? 1) + 1;
                $gatewayData[Constants::ATTEMPT] = $newAttempt;

                $entity->setGatewayData($gatewayData);
                $this->repo->saveOrFail($entity);
            }
            return $entity;
        }

        $mozartAction =$this->gatewayDataIdToActionMap[$action];

        $executeAt = null;
        // For notify call ICICI is expecting the execution, we are going to make it centralize
        if ($mozartAction === 'notify')
        {
            $executeAt = $input['payment']['created_at'] + $this->defaultExecuteBuffer;
        }

        // This is more like a hack for now, we need to see how its flowing on ICICI
        // And then we can make it more general approach
        // Here the sequence no is being passed in payment description as <text>seqno <seqno>
        $sequenceNo = explode('seqno ', $input['payment']['description'])[1] ?? 1;

        // Still giving preference to the hack as we have made couple of mandate and hit few notification api
        // For which the sequence numbers are not updated in this, this hack will let us test with any number
        if ($sequenceNo === 1)
        {
            $sequenceNo = $input['upi_mandate']['used_count'];
        }

        $attr = [
            Entity::VPA           => $input['payment']['vpa'] ?? null,
            Entity::TYPE          => $input['upi']['fow'] ?? null,
            Entity::STATUS_CODE   => 'pending',
            Entity::GATEWAY_DATA  => [
                // Action which mozart will send in request id
                Constants::ACTION     => $mozartAction,
                // Attempt NO which will be sent in request
                Constants::ATTEMPT    => 1,
                // Short for execution time
                Constants::EXECUTE_AT => $executeAt,
                // Seq number
                Constants::SEQUENCE   => $sequenceNo,
            ],
            Entity::GATEWAY_MERCHANT_ID     => $input['terminal']['gateway_merchant_id'],
        ];

        return $this->createGatewayPaymentEntity($attr, $action, false);
    }

    protected function shouldSkipNotityForAutoRecurring(array $input)
    {
        // As of now, we will skip notify based on frequency only
        return UpiMandate\Frequency::shouldSkipNotify($this->gateway, $input['upi_mandate']['frequency']);
    }

    protected function getNextRemindAtForRecurring(Entity $upi, array $response, BaseException $exception = null)
    {
        $action         = $upi->getAction();
        $attempt        = $upi->getGatewayData()[Constants::ATTEMPT];
        $remindAfter    = null;
        $success        = is_null($exception);

        // Three attempt for notification, next action is authorization when success
        if ($action === Action::PRE_DEBIT)
        {
            if ($success === false)
            {
                if ($attempt >= 3)
                {
                    return null;
                }

                // Starting with retries at 10 and 20 minutes
                $remindAfter = (pow(2, $attempt) * 5);
            }
            else
            {
                // 24 hours in minutes to be set for authorization
                $remindAfter = 1440;
            }
        }

        // Three attempt for authorize, no next reminder needed when success
        if ($action === Action::DEBIT)
        {
            if ($attempt >= 3)
            {
                return null;
            }

            // Starting with retries at 30 and 60 minutes
            $remindAfter =  (pow(2, $attempt) * 15);
        }

        return Carbon::now()->addMinutes($remindAfter)->getTimestamp();
    }

    protected function getResponseForAutoRecurring(array $input, int $remindAt = null, Entity $upi = null)
    {
        $response = [
            // Data which is needed for mandate
            'upi_mandate'   => [],
            // Data which is needed for UPI Metadata
            'upi'           => [
                'vpa'               => $input['payment']['vpa'],
                'umn'               => $input['upi_mandate']['umn'],
                // For Sharp Gateway, webhook will be almost instantaneous
                'remind_at'         => $remindAt,
            ],
        ];

        if ($upi instanceof Entity)
        {
            $details = [
                'rrn'             => $upi->getNpciReferenceId(),
                'npci_txn_id'     => $upi->getNpciTransactionId(),
                'reference'       => $upi->getMerchantReference(),
            ];

            // Details can be considered nullable, but for API side validation we need to remove nulls
            $response['upi'] = array_merge($response['upi'], array_filter($details));

            // Will only be used if we mark payment authorized and that is done by
            // sending upi.internal_status=authorized, thus this data will be ignored
            $response['acquirer'] = [
                Payment\Entity::REFERENCE1  => $upi->getNpciTransactionId(),
                Payment\Entity::REFERENCE16 => $upi->getNpciReferenceId(),
            ];
        }

        return $response;
    }
}
