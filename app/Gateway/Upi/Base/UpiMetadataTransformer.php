<?php

namespace RZP\Gateway\Upi\Base;

use Carbon\Carbon;
use RZP\Gateway\Base\Action;
use RZP\Models\Customer\Token;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\Payment\UpiMetadata\Entity as Metadata;
use RZP\Models\Payment\UpiMetadata\InternalStatus as InternalStatus;

class UpiMetadataTransformer extends UpiTransanformer
{
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
            $this->item->setVpa($this->upi->getVpa())
                       ->setUmn($this->response(Metadata::UMN))
                       ->setRrn($this->response(Metadata::RRN))
                       ->setNpciTxnId($this->response(Metadata::NPCI_TXN_ID));

            if ($this->isSuccess() === true)
            {
                $this->item->setInternalStatus(InternalStatus::AUTHENTICATE_INITIATED);

                $this->dataBlock = [
                    // This is the merchant VPA not the customer VPA which is supposed to
                    // sent to merchant/customer in the request co proto.
                    Entity::VPA => $this->response('terminal.vpa'),
                ];
            }
        }
        else if ($this->context->getAction() === Action::CALLBACK)
        {
            $this->item->setVpa($this->upi->getVpa())
                       ->setUmn($this->response(Metadata::UMN))
                       ->setRrn($this->response(Metadata::RRN))
                       ->setNpciTxnId($this->response(Metadata::NPCI_TXN_ID));

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
            $this->item->setVpa($this->upi->getVpa())
                ->setUmn($this->response(Metadata::UMN))
                ->setRrn($this->response(Metadata::RRN))
                ->setNpciTxnId($this->response(Metadata::NPCI_TXN_ID));

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
            $this->item->setUmn($this->response(Metadata::UMN))
                ->setRrn($this->response(Metadata::RRN))
                ->setNpciTxnId($this->response(Metadata::NPCI_TXN_ID));

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
            $this->item->setUmn($this->response(Metadata::UMN))
                       ->setRrn($this->response(Metadata::RRN))
                       ->setNpciTxnId($this->response(Metadata::NPCI_TXN_ID));

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
            $this->item->setUmn($this->response(Metadata::UMN))
                ->setRrn($this->response(Metadata::RRN))
                ->setNpciTxnId($this->response(Metadata::NPCI_TXN_ID));

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
            $this->item->setUmn($this->response(Metadata::UMN))
                       ->setVpa($this->upi->getVpa())
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

        // Three attempt for notification, next action is authorization when success
        if ($action === Action::PRE_DEBIT)
        {
            if ($this->isSuccess() === false)
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
                // 24+1 hours in minutes to be set for authorization
                $remindAfter = 1500;
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

        if (is_null($remindAfter) === true)
        {
            return null;
        }

        return Carbon::now()->addMinutes($remindAfter)->getTimestamp();
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
}
