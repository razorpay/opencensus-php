<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Mandate;
use RZP\Models\P2p\Mandate\Action;
use RZP\Exception\RuntimeException;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Mandate\UpiMandate;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;

use RZP\Models\P2p\Transaction\Status;

class UpiMandateTransformer extends Transformer
{

    /**
     * Transform basic fields
     *
     * @return array
     */
    public function transform(): array
    {
        switch ($this->action)
        {
            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED:
                $output = [
                    UpiMandate\Entity::ACTION    => Action::INCOMING_COLLECT,
                    UpiMandate\Entity::STATUS    => Status::REQUESTED,
                    UpiMandate\Entity::HANDLE    => $this->getVpaHandle($this->input[Fields::PAYER_VPA]),
                ];

                $this->input[Fields::GATEWAY_RESPONSE_CODE]     = '00';
                $this->input[Fields::GATEWAY_RESPONSE_MESSAGE]  = 'Incoming mandate collect request';

                break;
        }

        return $output;
    }

    /**
     * Transform UpiMandate and Mandate data from gateway for incoming collect mandate
     *
     * @return array
     * @throws RuntimeException
     */
    public function transformIncoming(): array
    {
        $request = $this->transform();

        $output = [
            UpiMandate\Entity::NETWORK_TRANSACTION_ID      => $this->input[Fields::GATEWAY_MANDATE_ID],
            UpiMandate\Entity::GATEWAY_TRANSACTION_ID      => $this->input[Fields::GATEWAY_MANDATE_ID],
            UpiMandate\Entity::GATEWAY_REFERENCE_ID        => $this->input[Fields::GATEWAY_REFERENCE_ID],
            UpiMandate\Entity::RRN                         => $this->input[Fields::GATEWAY_REFERENCE_ID],
            UpiMandate\Entity::GATEWAY_ERROR_CODE          => $this->input[Fields::GATEWAY_RESPONSE_CODE],
            UpiMandate\Entity::GATEWAY_ERROR_DESCRIPTION   => $this->input[Fields::GATEWAY_RESPONSE_MESSAGE],
            UpiMandate\Entity::MCC                         => $this->input[Fields::PAYEE_MCC] ?? '',
            UpiMandate\Entity::REF_URL                     => $this->input[Fields::REF_URL] ?? '',
        ];

        $output[UpiMandate\Entity::GATEWAY_DATA] = array_only($this->input, [
            Fields::TYPE,
            Fields::MANDATE_TIMESTAMP,
            Fields::ORG_MANDATE_ID,
        ]);

        $output[Mandate\Entity::MANDATE] = $this->transformMandate();

        return array_merge($request, $output);
    }

    /**
     * Transform Mandate data based on action
     *
     * @return array
     * @throws RuntimeException
     */
    public function transformMandate()
    {
        switch ($this->action)
        {
            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED:
                $payer = $this->toUsernameHandle($this->input[Fields::PAYER_VPA]);

                $payee = $this->toUsernameHandle($this->input[Fields::PAYEE_VPA]);
                $payee[Vpa\Entity::BENEFICIARY_NAME] = $this->input[Fields::PAYEE_NAME];

                $expiryAt   = $this->transformExpireAt();
                $startDate  = Carbon::parse($this->input[Fields::VALIDITY_START])->getTimestamp();
                $endDate    = Carbon::parse($this->input[Fields::VALIDITY_END])->getTimestamp();

                break;

            default:
                throw new RuntimeException('Action'. $this->action . 'is not known');
        }

        $output = [
            Mandate\Entity::NAME                    => $this->input[Fields::MANDATE_NAME],
            Mandate\Entity::MODE                    => Mandate\Mode::DEFAULT,
            Mandate\Entity::CURRENCY                => 'INR',
            Mandate\Entity::AMOUNT                  => $this->toPaisa($this->input[Fields::AMOUNT]),
            Mandate\Entity::AMOUNT_RULE             => $this->input[Fields::AMOUNT_RULE],
            Mandate\Entity::DESCRIPTION             => $this->input[Fields::REMARKS] ?? 'No Remarks',
            Mandate\Entity::RECURRING_TYPE          => $this->input[Fields::RECURRENCE_PATTERN],
            Mandate\Entity::UMN                     => $this->input[Fields::UMN],
            Mandate\Entity::START_DATE              => $startDate,
            Mandate\Entity::END_DATE                => $endDate,
            Mandate\Entity::PAYER                   => $payer,
            Mandate\Entity::PAYEE                   => $payee,
            Mandate\Entity::EXPIRE_AT               => $expiryAt,
        ];

        if (isset($this->input[Fields::RECURRENCE_RULE]) === true)
        {
            $output[Mandate\Entity::RECURRING_RULE] = $this->input[Fields::RECURRENCE_RULE];
        }

        if (isset($this->input[Fields::RECURRENCE_VALUE]) === true)
        {
            $output[Mandate\Entity::RECURRING_VALUE] = $this->toInteger($this->input[Fields::RECURRENCE_VALUE]);
        }

        return $output;
    }

    /**
     * get handle from vpa
     *
     * @param $vpa
     * @return string
     */
    public function getVpaHandle($vpa)
    {
        return explode(Vpa\Entity::AEROBASE, $vpa)[1];
    }

    public function transformExpireAt()
    {
        switch ($this->action)
        {
            case UpiAction::CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED:
                return Carbon::parse($this->input[Fields::EXPIRY])->getTimestamp();
        }
    }
}
