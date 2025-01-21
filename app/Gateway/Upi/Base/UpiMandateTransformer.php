<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;
use RZP\Exception\LogicException;
use RZP\Models\UpiMandate\Metrics;
use RZP\Models\UpiMandate\Status as Status;
use RZP\Models\UpiMandate\Entity as Mandate;

class UpiMandateTransformer extends UpiTransformer
{
    /**
     * @var Mandate
     */
    protected $item;

    /**
     * This method will build a proper mandate entity, which will let API to identify the mandate
     * and update all necessary fields. The response will be agnostic if the current request action.
     */
    public function transform(): Mandate
    {
        $this->item = new Mandate();

        // We will try to identify the status of mandate. This logic is not final and can be improved over time.
        // Also, even though we are taking the input(sent to mozart) as parameter our logic should not rely in that.
        if (is_null($this->upi) === true)
        {
            $this->anomalies->logic('Upi entity should not be null for mandate response', $this->response);

            return [];
        }

        switch ($this->upi->getAction())
        {
            // Mandate create callback
            case Action::AUTHENTICATE:
                $this->processResponseForAuthenticate();
                break;

            case Action::AUTHORIZE:
            case Action::DEBIT:
                $this->processResponseForAuthorize();
                break;
        }

        return $this->item;
    }

    public function toArray()
    {
        // Mandate edit will not accept the null values
        $array = array_filter(parent::toArray());

        // Entity sets two extra fields in the to array which can't be fill
        return array_except(
            $array,
            [
                Mandate::CONFIRMED_AT,
                Mandate::LATE_CONFIRMED,
            ]);
    }

    protected function getResponseArray(): array
    {
        return $this->response->getMandate();
    }

    protected function processResponseForAuthenticate()
    {
        $app = \App::getFacadeRoot();
        $action = $this->context->getAction() ?? $this->input['action'];

        if ($action === Action::AUTHENTICATE)
        {
            $this->item->setStatus(Status::CREATED);

            if ($this->isSuccess() === true)
            {
                //TODO:  We need to send the status initiated in this case
                //$this->item->setStatus(Status::INITIATED);
            }
            $this->updateMetadataFromResponse();
        }
        else if (($action === Action::CALLBACK) or ($action === Action::RECURRING_CALLBACK))
        {
            $this->item->setStatus(Status::CREATED);

            if ($this->isSuccess() === true)
            {
                $this->item->setStatus(Status::CONFIRMED);

                $app['trace']->count(Metrics::UPI_AUTOPAY_MANDATE_CONFIRMED, [
                    'gateway' => $this->input['payment']['gateway'],
                    'is_tpv'  => $this->input['terminal']['tpv'],
                    'flow'    => $this->input['upi']['flow']
                ]);
            }
            else
            {
                $internalErrorCode = $this->exception->getError()->getInternalErrorCode();

                // If the mandate is rejected by user, update mandate status as rejected.
                if (($internalErrorCode === ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED) or
                    ($internalErrorCode === ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_BY_CUSTOMER))
                {
                    $this->item->setStatus(Status::REJECTED);

                    $app['trace']->count(Metrics::UPI_AUTOPAY_MANDATE_REJECTED, [
                        'gateway' => $this->input['payment']['gateway'],
                        'is_tpv'  => $this->input['terminal']['tpv'],
                        'flow'    => $this->input['upi']['flow']
                    ]);
                }
            }

            $this->updateMetadataFromResponse();
        }
        else if ($action === Action::VERIFY)
        {
            $this->item->setStatus(Status::CREATED);

            if ($this->isSuccess() === true)
            {
                $this->item->setStatus(Status::CONFIRMED);
            }

            $this->updateMetadataFromResponse();
        }
        else
        {
            throw new LogicException('Not implemented for authenticate');
        }
    }

    protected function processResponseForAuthorize()
    {
        if (($this->input['payment']['recurring_type'] === 'initial') and
            (in_array($this->input['payment']['gateway'], RecurringTrait::$optimizerUpiRecurringGateway) === true) and
            ($this->context->getAction() === Action::CALLBACK))
        {
            $this->item->setStatus(Status::CREATED);

            if ($this->isSuccess() === true)
            {
                $this->item->setStatus(Status::CONFIRMED);
                $this->item->setUmn($this->input['gateway']['mihpayid']);

            }
            else
            {
                $internalErrorCode = $this->exception->getError()->getInternalErrorCode();

                // If the mandate is rejected by user, update mandate status as rejected.
                if ($internalErrorCode === ErrorCode::BAD_REQUEST_PAYMENT_UPI_MANDATE_REJECTED)
                {
                    $this->item->setStatus(Status::REJECTED);
                }
            }

        }

    }

    /**
     * Sets the value of UMN, RRN, and NPCI Transaction ID from the response
     *
     * @return UpiTransformer
     */
    protected function updateMetadataFromResponse(): UpiTransformer
    {
        $this->item->setUmn($this->response(Mandate::UMN))
             ->setRrn($this->response(Mandate::RRN))
             ->setNpciTxnId($this->response(Mandate::NPCI_TXN_ID));

        return $this;
    }
}
