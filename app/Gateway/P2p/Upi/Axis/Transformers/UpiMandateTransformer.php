<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Mandate;
use RZP\Models\P2p\Mandate\Status;
use RZP\Models\P2p\Mandate\Action;
use RZP\Exception\RuntimeException;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Mandate\UpiMandate;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Gateway\P2p\Upi\Axis\Actions\MandateAction;

class UpiMandateTransformer extends Transformer
{

    /**
     * Transform basic fields
     *
     * @return array
     */
    public function transform(): array
    {
        $output = [];

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

            case UpiAction::CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED:
                $output = [
                    UpiMandate\Entity::ACTION    => Action::INCOMING_UPDATE,
                    UpiMandate\Entity::STATUS    => Status::UPDATED,
                    UpiMandate\Entity::HANDLE    => $this->getVpaHandle($this->input[Fields::PAYER_VPA]),
                ];

                $this->input[Fields::GATEWAY_RESPONSE_CODE]     = '00';
                $this->input[Fields::GATEWAY_RESPONSE_MESSAGE]  = 'Incoming mandate update request';
                break;

            case UpiAction::CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED:
                $output = [
                    UpiMandate\Entity::ACTION    => Action::INCOMING_PAUSE,
                    UpiMandate\Entity::STATUS    => Status::PAUSED,
                    UpiMandate\Entity::HANDLE    => $this->getVpaHandle($this->input[Fields::PAYER_VPA]),
                ];

                $this->input[Fields::GATEWAY_RESPONSE_CODE]     = '00';
                $this->input[Fields::GATEWAY_RESPONSE_MESSAGE]  = 'Incoming mandate pause request';
                break;

            case MandateAction::PAUSED:
                $output = [
                    UpiMandate\Entity::ACTION   => Action::INITIATE_PAUSE,
                    UpiMandate\Entity::STATUS   => Status::PAUSED,
                ];
                break;

            case MandateAction::UNPAUSED:
                $output = [
                    UpiMandate\Entity::ACTION   => Action::INITIATE_UNPAUSE,
                    UpiMandate\Entity::STATUS   => Status::UNPAUSED,
                ];
                break;

            case MandateAction::SUCCESS:
                $output = [
                    UpiMandate\Entity::ACTION    => Action::INITIATE_AUTHORIZE,
                    UpiMandate\Entity::STATUS    => Mandate\Status::APPROVED,
                ];
                break;

            case MandateAction::DECLINED:
                $output = [
                    UpiMandate\Entity::ACTION    => Action::INITIATE_REJECT,
                    UpiMandate\Entity::STATUS    => Mandate\Status::REJECTED,
                ];
                break;

            case MandateAction::FAILURE:
                $output = [
                    UpiMandate\Entity::ACTION    => $this->action,
                    UpiMandate\Entity::STATUS    => Mandate\Status::FAILED,
                ];
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
            case UpiAction::CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED:
            case UpiAction::CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED:
                $payer = $this->toUsernameHandle($this->input[Fields::PAYER_VPA]);

                $payee = $this->toUsernameHandle($this->input[Fields::PAYEE_VPA]);
                $payee[Vpa\Entity::BENEFICIARY_NAME] = $this->input[Fields::PAYEE_NAME];

                $expiryAt   = $this->transformExpireAt();
                $startDate  = Carbon::parse($this->input[Fields::VALIDITY_START])->getTimestamp();
                $endDate    = Carbon::parse($this->input[Fields::VALIDITY_END])->getTimestamp();

                break;

            case UpiAction::CUSTOMER_INCOMING_MANDATE_PAUSE_REQUEST_RECEIVED:
                $payer = $this->toUsernameHandle($this->input[Fields::PAYER_VPA]);

                $payee = $this->toUsernameHandle($this->input[Fields::PAYEE_VPA]);
                $payee[Vpa\Entity::BENEFICIARY_NAME] = $this->input[Fields::PAYEE_NAME];

                $expiryAt        = $this->transformExpireAt();
                $startDate       = Carbon::parse($this->input[Fields::VALIDITY_START])->getTimestamp();
                $endDate         = Carbon::parse($this->input[Fields::VALIDITY_END])->getTimestamp();
                $pauseStartDate  = isset($this->input[Fields::PAUSE_START]) ? Carbon::parse($this->input[Fields::PAUSE_START])->getTimestamp(): 0;
                $pauseEndDate    = isset($this->input[Fields::PAUSE_END]) ? Carbon::parse($this->input[Fields::PAUSE_END])->getTimestamp(): 0;
                break;

            case UpiAction::MANDATE_STATUS_UPDATE:
                $payer = $this->toUsernameHandle($this->input[Fields::PAYER_VPA]);

                $payee = $this->toUsernameHandle($this->input[Fields::PAYEE_VPA]);
                $payee[Vpa\Entity::BENEFICIARY_NAME] = $this->input[Fields::PAYEE_NAME];

                $expiryAt        = $this->transformExpireAt();
                $startDate       = Carbon::parse($this->input[Fields::VALIDITY_START])->getTimestamp();
                $endDate         = Carbon::parse($this->input[Fields::VALIDITY_END])->getTimestamp();
                $pauseStartDate  = isset($this->input[Fields::PAUSE_START]) ? Carbon::parse($this->input[Fields::PAUSE_START])->getTimestamp(): 0;
                $pauseEndDate    = isset($this->input[Fields::PAUSE_END]) ? Carbon::parse($this->input[Fields::PAUSE_END])->getTimestamp(): 0;

                // if the state of the mandate is in completed state mark it as completed
                if(isset($this->input[Fields::STATUS]) === true)
                {
                    switch ($this->input[Fields::STATUS])
                    {
                        case MandateAction::COMPLETED:
                            $this->input[Mandate\Entity::STATUS]          = Mandate\Status::COMPLETED;
                            $this->input[Mandate\Entity::INTERNAL_STATUS] = Mandate\Status::COMPLETED;
                            break;

                        case MandateAction::SUCCESS:
                            $this->input[Mandate\Entity::STATUS]          = Mandate\Status::APPROVED;
                            $this->input[Mandate\Entity::INTERNAL_STATUS] = Mandate\Status::APPROVED;
                            break;

                        case MandateAction::PAUSE:
                            $this->input[Mandate\Entity::STATUS]          = Mandate\Status::PAUSED;
                            $this->input[Mandate\Entity::INTERNAL_STATUS] = Mandate\Status::PAUSED;
                            break;
                    }
                }
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

        if (isset($this->input[Fields::PAUSE_START]) === true)
        {
            $output[Mandate\Entity::PAUSE_START]   = Carbon::parse($this->input[Fields::PAUSE_START])->getTimestamp();
        }

        if (isset($this->input[Fields::PAUSE_END]) === true)
        {
            $output[Mandate\Entity::PAUSE_END]   = Carbon::parse($this->input[Fields::PAUSE_END])->getTimestamp();
        }

        if(isset($this->input[Fields::STATUS]) === true)
        {
            $output[Mandate\Entity::STATUS] = $this->input[Fields::STATUS];
        }

        if(isset($this->input[Mandate\Entity::INTERNAL_STATUS]) === true)
        {
            $output[Mandate\Entity::INTERNAL_STATUS] = $this->input[Mandate\Entity::INTERNAL_STATUS];
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
            case UpiAction::CUSTOMER_INCOMING_MANDATE_UPDATE_REQUEST_RECEIVED:
                return Carbon::parse($this->input[Fields::EXPIRY])->getTimestamp();
        }
    }

    public function transformSdk(): array
    {
        $request = $this->transform();

        $output = [
            UpiMandate\Entity::MANDATE_ID                             => $this->transformTransactionId(),
            UpiMandate\Entity::NETWORK_TRANSACTION_ID                 => $this->input[Fields::GATEWAY_MANDATE_ID],
            UpiMandate\Entity::GATEWAY_TRANSACTION_ID                 => $this->input[Fields::GATEWAY_MANDATE_ID],
            UpiMandate\Entity::GATEWAY_REFERENCE_ID                   => $this->input[Fields::GATEWAY_REFERENCE_ID],
            UpiMandate\Entity::RRN                                    => $this->input[Fields::GATEWAY_MANDATE_ID],
            UpiMandate\Entity::GATEWAY_ERROR_CODE                     => $this->input[Fields::GATEWAY_RESPONSE_CODE],
            UpiMandate\Entity::GATEWAY_ERROR_DESCRIPTION              => $this->input[Fields::GATEWAY_RESPONSE_MESSAGE],
        ];


        $gatewayData = array_only($this->input,[Fields::MERCHANT_REQUEST_ID,
                                                Fields::GATEWAY_RESPONSE_STATUS,
                                                Fields::ORG_MANDATE_ID,
                                                Fields::MANDATE_TIMESTAMP]);

        if(isset($this->input[Mandate\Entity::UPI]))
            $output[UpiMandate\Entity::GATEWAY_DATA] = array_merge($this->input[Mandate\Entity::UPI][UpiMandate\Entity::GATEWAY_DATA],$gatewayData);
        else
            $output[UpiMandate\Entity::GATEWAY_DATA] = $gatewayData;


        $output[Mandate\Entity::MANDATE] = [
            Mandate\Entity::ID               => $this->transformTransactionId(),
            Mandate\Entity::AMOUNT           => $this->toPaisa($this->input[Fields::AMOUNT]),
            Mandate\Entity::NAME             => $this->input[Fields::MANDATE_NAME],
            Mandate\Entity::TYPE             => $this->input[Fields::MANDATE_TYPE],
            Mandate\Entity::COMPLETED_AT     => isset($this->input[Fields::MANDATE_APPROVAL_TIMESTAMP]) ? $this->input[Fields::MANDATE_APPROVAL_TIMESTAMP] :'',
            Mandate\Entity::AMOUNT_RULE      => $this->input[Fields::AMOUNT_RULE],
            Mandate\Entity::RECURRING_TYPE   => $this->input[Fields::RECURRENCE_PATTERN],
            Mandate\Entity::RECURRING_RULE   => $this->input[Fields::RECURRENCE_RULE],
            Mandate\Entity::RECURRING_VALUE  => $this->toInteger($this->input[Fields::RECURRENCE_VALUE]),
            Mandate\Entity::START_DATE       => isset($this->input[Fields::VALIDITY_START])? Carbon::parse($this->input[Fields::VALIDITY_START])->getTimestamp() :0,
            Mandate\Entity::END_DATE         => isset($this->input[Fields::VALIDITY_END])? Carbon::parse($this->input[Fields::VALIDITY_END])->getTimestamp() :0,
            Mandate\Entity::DESCRIPTION      => $this->input[Fields::REMARKS],
            Mandate\Entity::EXPIRE_AT        => isset($this->input[Fields::EXPIRY])? Carbon::parse($this->input[Fields::EXPIRY])->getTimestamp() :0,
            Mandate\Entity::UMN              => $this->input[Fields::UMN],
            Mandate\Entity::PAUSE_START      => isset($this->input[Fields::PAUSE_START]) ? Carbon::parse($this->input[Fields::PAUSE_START])->getTimestamp(): 0,
            Mandate\Entity::PAUSE_END       =>  isset($this->input[Fields::PAUSE_END]) ? Carbon::parse($this->input[Fields::PAUSE_END])->getTimestamp(): 0,
        ];

        return array_merge($request, $output);
    }

    public function transformTransactionId()
    {
        if (isset($this->input[Fields::MERCHANT_REQUEST_ID]) === true)
        {
            return substr($this->input[Fields::MERCHANT_REQUEST_ID], -14);
        }
    }
}
