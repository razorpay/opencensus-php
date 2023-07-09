<?php

namespace RZP\Gateway\Upi\Base;

use App;
use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\UpiMandate\Frequency;
use RZP\Models\UpiMandate\RecurringType;
use RZP\Models\Payment\Processor\UpiRecurring;
use RZP\Models\Payment\UpiMetadata\Mode as Mode;
use RZP\Models\Payment\UpiMetadata\Entity as Metadata;
use RZP\Models\Payment\UpiMetadata\InternalStatus as InternalStatus;

class UpiMetadataTransformer extends UpiTransformer
{
    use UpiRecurring;

    /**
     * @var Metadata
     */
    protected $item;

    /**
     * @var A data block which can be sent to customer when intervention is needed
     */
    protected $dataBlock;

    public function transform(): Metadata
    {
        $this->item = new Metadata();

        if (is_null($this->upi) === true)
        {
            $this->anomalies->logic('Upi entity should not be null for metadata response', $this->response);

            return [];
        }

        switch ($this->upi->getAction())
        {
            // Mandate create callback
            case Action::AUTHENTICATE:
                $this->processResponseForAuthenticate();
                break;

            case Action::AUTHORIZE:
                $this->processResponseForAuthorize();
                break;

            case Action::PRE_DEBIT:
                $this->processResponseForPreDebit();
        }

        $this->item->setRemindAt($this->getNextRemindAtForRecurring());

        return $this->item;
    }

    protected function getResponseArray(): array
    {
        return $this->response->getUpi();
    }

    protected function processResponseForAuthenticate()
    {
        if ($this->context->getAction() === Action::AUTHENTICATE)
        {
            $this->updateMetadataFromResponse()
                 ->setVpa();

            if ($this->isSuccess() === true)
            {
                $this->item->setInternalStatus(InternalStatus::AUTHENTICATE_INITIATED);

                if ($this->isTypeIntent() === true)
                {
                    $this->dataBlock = [
                        // This is the mandate url that needs to be sent in the response
                        // in case of intent flow for recurring
                        'intent_url' => $this->response->getIntentUrl()
                    ];
                }
                else
                {
                    $this->dataBlock = [
                        // This is the merchant VPA not the customer VPA which is supposed to
                        // sent to merchant/customer in the request co proto.
                        Entity::VPA => $this->response('terminal.vpa'),
                    ];
                }
            }
        }
        else if ($this->context->getAction() === Action::CALLBACK)
        {
            $this->updateMetadataFromResponse()
                 ->setVpa();

            if ($this->isSuccess() === true)
            {
                // Callback on authenticate entity with success means that mandate is created
                // successfully and now first debit is pending for the mandate
                $this->item->setInternalStatus(InternalStatus::PENDING_FOR_AUTHORIZE);
            }
            else
            {
                $this->item->setInternalStatus(InternalStatus::FAILED);
            }
        }
        else if ($this->context->getAction() === Action::VERIFY)
        {
            $this->updateMetadataFromResponse()
                 ->setVpa();

            if ($this->isSuccess() === true)
            {
                // Callback on authenticate entity with success means that mandate is created
                // successfully and now first debit is pending for the mandate
                $this->item->setInternalStatus(InternalStatus::PENDING_FOR_AUTHORIZE);
            }
        }
        else
        {
            throw new LogicException('Not implemented for authenticate');
        }
    }

    protected function processResponseForAuthorize()
    {
        if ($this->context->getAction() === Action::DEBIT)
        {
            $this->updateMetadataFromResponse()
                 ->setVpa();

            if ($this->isSuccess() === true)
            {
                // S2S response for authenticate with success means that now callback/verify is pending from gateway
                $this->item->setInternalStatus(InternalStatus::AUTHORIZE_INITIATED);
            }
            else
            {
                $this->item->setInternalStatus(InternalStatus::FAILED);
            }
        }
        else if ($this->context->getAction() === Action::CALLBACK)
        {
            $this->updateMetadataFromResponse()
                 ->setVpa();

            if ($this->isSuccess() === true)
            {
                // Callback on authorize entity with success means that the debit was successful
                // For both First Debit and Auto debit this logic holds true
                $this->item->setInternalStatus(InternalStatus::AUTHORIZED);
            }
            else {
                $this->item->setInternalStatus(InternalStatus::FAILED);
            }
        }
        else if ($this->context->getAction() === Action::VERIFY)
        {
            $this->updateMetadataFromResponse()
                 ->setVpa();

            if ($this->isSuccess() === true)
            {
                // Callback on authorize entity with success means that the debit was successful
                // For both First Debit and Auto debit this logic holds true
                $this->item->setInternalStatus(InternalStatus::AUTHORIZED);
            }
        }
        else
        {
            throw new LogicException('Not implemented for authorize');
        }
    }

    protected function processResponseForPreDebit()
    {
        if ($this->context->getAction() === Action::PRE_DEBIT)
        {
            $this->setVpa();

            $this->item->setUmn($this->response(Metadata::UMN))
                       ->setRrn($this->upi->getNpciReferenceId())
                       ->setNpciTxnId($this->upi->getNpciTransactionId());

            if ($this->isSuccess() === true)
            {
                $this->item->setInternalStatus(InternalStatus::PRE_DEBIT_INITIATED);
            }
        }
        else
        {
            throw new LogicException('Not implemented for pre debit');
        }
    }

    protected function getNextRemindAtForRecurring()
    {
        $action         = $this->upi->getAction();
        $attempt        = $this->upi->getGatewayData()[Constants::ATTEMPT];
        $remindAfter    = null;
        $mode           = $this->input[Entity::UPI][Metadata::MODE];

        // Three attempt for notification, next action is authorization when success
        if ($action === Action::PRE_DEBIT)
        {
            if ($this->isSuccess() === false)
            {
                if ($attempt >= 3)
                {
                    return null;
                }

                // check gateway status code is mandate revoke or pause then do not retry the payment
                if(((new \RZP\Gateway\Upi\Icici\Gateway())->checkGatewayStatusAndUpdateEntity
                    ($this->response['status_code'],$this->input[Entity::PAYMENT]['merchant_id'])) === true)
                {
                    return null;
                }

                // Starting with retries at 10 and 20 minutes
                $remindAfter = (pow(2, $attempt) * 5);
            }
            else
            {
                // 24+1 hours in minutes to be set for authorization
                $remindAfter = 1500;
            }
        }

        // Three attempt for authorize (for subsequent debits only i.e. mode === auto),
        // no next reminder needed when success
        if ($action === Action::AUTHORIZE and $mode === Mode::AUTO)
        {
            if($this->isSuccess() === true)
            {
                return null;
            }

            $canRetry = $this->checkUpiAutopayIncreaseDebitRetry($this->input[Entity::PAYMENT]['id'], $this->input[Entity::PAYMENT]['merchant_id'], $this->upi);

            if ((($canRetry === true) and ($attempt >= 10)) or (($canRetry === false) and ($attempt >= 3)))
            {
                return null;
            }

            // Remind after 5 hours if experiment is on.
            if ($canRetry === true)
            {
                $remindAfter = 300;
            }
            else
            {
                // Starting with retries at 30 and 60 minutes
                $remindAfter =  (pow(2, $attempt) * 15);
            }

            $upiMandate = $this->input['upi_mandate'] ?? null;

            if((empty($remindAfter) === false) and
                ($upiMandate !== null) and
                ($upiMandate['frequency'] !== Frequency::AS_PRESENTED) and
                ($upiMandate['frequency'] !== Frequency::DAILY))
            {
                if(($this->isValidMandateExpiry($upiMandate, $remindAfter) === false) or
                    ($this->isValidCycle($upiMandate, $remindAfter) === false))
                {
                    return null;
                }
            }
        }

        if (is_null($remindAfter) === true)
        {
            return null;
        }

        return Carbon::now()->addMinutes($remindAfter)->getTimestamp();
    }

    protected function isValidMandateExpiry($upiMandate, $remindAfter)
    {
        $reminderAfterTime = Carbon::now()->addMinutes($remindAfter)->getTimestamp();

        // handle for mandate expiry condition
        $mandateExpiry = $upiMandate['end_time'];
        $diffInMins = floor(($mandateExpiry-$reminderAfterTime)/60);

        if($diffInMins <= 5)
        {
            return false;
        }
        return true;
    }

    protected function isValidCycle($upiMandate, $remindAfter)
    {
        $frequency = $upiMandate['frequency'];
        $reminderAfterTime = Carbon::now()->addMinutes($remindAfter)->getTimestamp();
        $currentDay = Carbon::now(Timezone::IST)->day;

        $endOfCycle = Carbon::now(Timezone::IST)->endOfMonth()->day;

        if($frequency === Frequency::WEEKLY)
        {
            $currentDay = Carbon::now(Timezone::IST)->dayOfWeek;
            if($currentDay === 0)
            {
                $currentDay = 7;
            }
            $endOfCycle = 7;
        }

        $recurVal = $upiMandate['recurring_value'];
        $recurType = $upiMandate['recurring_type'];

        switch ($recurType)
        {
            case RecurringType::BEFORE:

                $diffInDays = abs($recurVal - $currentDay);
                $nextExecutionTime = Carbon::now(Timezone::IST)->addDays($diffInDays)->endOfDay()->getTimestamp();
                $diffInMin = floor(($nextExecutionTime-$reminderAfterTime)/60);

                return (($currentDay <= $recurVal) and ($diffInMin >= 5));

            case RecurringType::ON:
                $diff = $recurVal-$currentDay;
                if(($recurVal == 1) and ($currentDay === $endOfCycle))
                {
                    $diff = 1;
                }
                $nextExecutionTime = Carbon::now(Timezone::IST)->addDays($diff)->endOfDay()->getTimestamp();
                $diffInMin = floor(($nextExecutionTime-$reminderAfterTime)/60);
                return (($diff ==1) and ($diffInMin >=5));

            case RecurringType::AFTER:

                $diffInDays = abs($endOfCycle - $currentDay);
                $nextExecutionDay = Carbon::now(Timezone::IST)->addDays($diffInDays)->endOfDay()->getTimestamp();
                $diffInMin = floor(($nextExecutionDay-$reminderAfterTime)/60);

                return (($currentDay >= $recurVal) and ($diffInMin >= 5));

            default:
                return false;
        }
    }


    /**
     * Updates the following attributes of UPI Metadata:
     *  UMN, RRN, and NPCI Transaction ID
     *
     * @return UpiTransformer
     */
    protected function updateMetadataFromResponse(): UpiTransformer
    {
        $this->item->setUmn($this->response(Metadata::UMN))
             ->setRrn($this->response(Metadata::RRN))
             ->setNpciTxnId($this->response(Metadata::NPCI_TXN_ID));

        return $this;
    }

    public function toArray()
    {
        $array = parent::toArray();

        $remindAt = array_pull($array, Metadata::REMIND_AT);

        // Remove all nulls
        $array = array_filter($array);

        // RemindAt needs to be null for API to process,
        // TODO: Once the API side code is fixed and regressive verified , we can allow nulls for other too
        $array[Metadata::REMIND_AT] = $remindAt;

        return $array;
    }

    public function getDataBlock()
    {
        return $this->dataBlock;
    }

    /**
     * @return bool
     */
    private function isTypeIntent(): bool
    {
        return ($this->upi->getType() === Type::INTENT);
    }

    /**
     * @return bool
     */
    private function isTypeCollect(): bool
    {
        return ($this->upi->getType() === Type::COLLECT);
    }

    /**
     * @return UpiTransformer
     */
    private function setVpa(): UpiTransformer
    {
        $vpa = $this->upi->getVpa() ?? $this->response('upi.vpa') ?? null;

        // VPA should not be null in the following cases:
        //  if the flow is collect
        //  if the flow is intent and the transaction is beyond mandate create response
        if (is_null($vpa))
        {
            if (($this->isTypeCollect() === true) or
                ($this->isTypeIntent() === true and $this->context->getAction() !== Action::AUTHENTICATE))
            {
                $this->anomalies->missing(Entity::VPA);
            }

            $vpa = '';
        }

        $this->item->setVpa($vpa);

        return $this;
    }
}
