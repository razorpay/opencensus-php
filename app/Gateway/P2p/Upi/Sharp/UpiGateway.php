<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Mandate;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Gateway\P2p\Base\Response;
use RZP\Models\P2p\Mandate\UpiMandate;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Gateway\P2p\Upi\Sharp\Actions\UpiAction;

class UpiGateway extends Gateway implements Contracts\UpiGateway
{
    public function initiateGatewayCallback(Response $response)
    {
        $content = $this->input->get(Fields::CONTENT);

        $type = isset($content[Fields::TYPE]) ? $content[Fields::TYPE] : '';

        switch ($type)
        {
            case UpiAction::INCOMING_MANDATE_CREATE:
                $content[Mandate\Entity::STATUS]          = Mandate\Status::REQUESTED;
                $content[Mandate\Entity::INTERNAL_STATUS] = Mandate\Status::REQUESTED;
                $context = $this->transformIncomingMandateContext();
                $mandate = $this->transformMandate($content);
                $upi     = $this->transformUpiMandate($content);

                $response->setData([
                    Mandate\Entity::MANDATE     => $mandate,
                    Mandate\Entity::UPI         => $upi,
                    Mandate\Entity::CONTEXT     => $context,
                ]);

                break;

            default:
                $context = [
                    RegisterToken\Entity::ENTITY        => Device\Entity::REGISTER_TOKEN,
                    RegisterToken\Entity::ACTION        => Device\Action::VERIFICATION_SUCCESS,
                ];

                $registerToken = [
                    RegisterToken\Entity::TOKEN         => $content[Fields::TOKEN] ?? null,
                    RegisterToken\Entity::DEVICE_DATA   => [
                        'contact'   => $content[Fields::CONTACT] ?? null,
                    ],
                ];

                $response->setData([
                    Device\Entity::REGISTER_TOKEN   => $registerToken,
                    Device\Entity::CONTEXT          => $context,
                ]);
        }
    }

    public function gatewayCallback(Response $response)
    {
        $this->input->put(Device\Entity::RESPONSE, [
            Device\Entity::SUCCESS => true,
        ]);

        $response->setData($this->input->toArray());
    }

    /**
     * Convert VPA string to username, handle array
     *
     * @param string $vpaString
     *
     * @return array
     */
    private function toUsernameHandle(string $vpaString)
    {
        $vpa = explode(Vpa\Entity::AEROBASE, $vpaString);

        if ($vpa === [])
        {
            return [];
        }

        return [
            Vpa\Entity::USERNAME    => $vpa[0],
            Vpa\Entity::HANDLE      => $vpa[1] ?? null,
        ];
    }

    /**
     * Transform context fields for incoming mandate
     *
     * @return array
     */
    private function transformIncomingMandateContext()
    {
        return [
            Mandate\Entity::ENTITY        => Mandate\Entity::MANDATE,
            Mandate\Entity::ACTION        => Mandate\Action::INCOMING_COLLECT,
        ];
    }

    /**
     * Transform gateway mandate related fields to attribute names
     *
     * @param $content
     *
     * @return array
     */
    private function transformMandate($content): array
    {
        $mandate = [
            Mandate\Entity::TYPE                    => Mandate\Type::COLLECT,
            Mandate\Entity::FLOW                    => Mandate\Flow::DEBIT,
            Mandate\Entity::INTERNAL_STATUS         => Mandate\Status::REQUESTED,

            Mandate\Entity::MODE                    => Mandate\Mode::DEFAULT,
            Mandate\Entity::AMOUNT                  => $content[Fields::AMOUNT],
            Mandate\Entity::AMOUNT_RULE             => $content[Fields::AMOUNT_RULE] ?? 'EXACT',
            Mandate\Entity::CURRENCY                => 'INR',
            Mandate\Entity::DESCRIPTION             => $content[Fields::TRANSACTION_NOTE] ?? 'no remarks',
            Mandate\Entity::RECURRING_RULE          => $content[Fields::RECUR_TYPE] ?? '',
            Mandate\Entity::RECURRING_TYPE          => $content[Fields::RECUR] ?? '',
            Mandate\Entity::RECURRING_VALUE         => $content[Fields::RECUR_VALUE] ?? '',
            Mandate\Entity::START_DATE              => $content[Fields::VALIDITY_START],
            Mandate\Entity::END_DATE                => $content[Fields::VALIDITY_END],
            Mandate\Entity::STATUS                  => $content[Fields::STATUS],
            Mandate\Entity::INTERNAL_STATUS         => $content[Fields::STATUS],
            Mandate\Entity::PAYER                   => $this->toUsernameHandle($content[Fields::PAYER_VPA]),
            Mandate\Entity::PAYEE                   => $this->toUsernameHandle($content[Fields::PAYEE_VPA]),
            Mandate\Entity::EXPIRE_AT               => Carbon::tomorrow()->getTimestamp(),
        ];

        $mandate[Mandate\Entity::PAYEE][Vpa\Entity::BENEFICIARY_NAME] = 'rzp';

        $mandate[Mandate\Entity::HANDLE] = $mandate[Mandate\Entity::PAYER][Mandate\Entity::HANDLE];

        return $mandate;
    }

    /**
     * Transform gateway data to upiMandate attributes
     *
     * @param $content
     *
     * @return array
     */
    private function transformUpiMandate($content): array
    {
        // TODO: Change entity constants to upiMandate
        $handle = $this->toUsernameHandle($content[Fields::PAYER_VPA])[Mandate\Entity::HANDLE];

        $upiMandate = [
            UpiMandate\Entity::ACTION                      => Mandate\Action::INCOMING_COLLECT,
            UpiMandate\Entity::STATUS                      => Mandate\Status::REQUESTED,
            UpiMandate\Entity::HANDLE                      => $handle,
            UpiMandate\Entity::NETWORK_TRANSACTION_ID      => $content[Fields::TRANSACTION_REFERENCE] ?? '',
            UpiMandate\Entity::GATEWAY_TRANSACTION_ID      => $content[Fields::TRANSACTION_REFERENCE] ?? '',
            UpiMandate\Entity::GATEWAY_REFERENCE_ID        => $content[Fields::TRANSACTION_REFERENCE] ?? '',
            UpiMandate\Entity::RRN                         => $content[Fields::TRANSACTION_REFERENCE] ?? '',
            UpiMandate\Entity::MCC                         => $content[Fields::MCC] ?? '',
            UpiMandate\Entity::REF_URL                     => $content[Fields::URL] ?? '',
            UpiMandate\Entity::GATEWAY_ERROR_CODE          => '00',
            UpiMandate\Entity::GATEWAY_ERROR_DESCRIPTION   => 'Incoming collect request',
            UpiMandate\Entity::GATEWAY_DATA                => [],
        ];

        return $upiMandate;
    }
}
