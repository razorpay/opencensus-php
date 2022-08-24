<?php

namespace RZP\Tests\P2p\Service\Base;

use Carbon\Carbon;
use RZP\Gateway\P2p\Upi\Sharp\Fields as SharpFields;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Sharp\Actions\UpiAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class MandateHelper extends P2pHelper
{

    public function createMandate($gateway)
    {
        return $this->getCreateMandatePayload($gateway);
    }


    public function getCreateMandatePayload($gateway)
    {
        switch($gateway)
        {
            case 'p2p_upi_sharp':
                $gatewayMandateId = str_random(35);
                $request = [
                    SharpFields::TYPE                                        => UpiAction::INCOMING_MANDATE_CREATE,
                    SharpFields::AMOUNT                                      => 100,
                    SharpFields::AMOUNT_RULE                                 => 'MAX',
                    SharpFields::PAYER_VPA                                   => $this->fixtures->vpa(Fixtures::DEVICE_1)->getAddress(),
                    SharpFields::PAYEE_VPA                                   => 'username@randompsp',
                    SharpFields::VALIDITY_START                              => Carbon::now()->getTimestamp(),
                    SharpFields::VALIDITY_END                                => Carbon::now()->addDays(365)->getTimestamp(),
                    SharpFields::TRANSACTION_NOTE                            => 'UPI',
                    SharpFields::RECUR                                       => 'DAILY',
                    SharpFields::TRANSACTION_REFERENCE                       => $gatewayMandateId,
                ];

               return $request;

            case 'p2p_upi_axis':
                $gatewayMandateId = str_random(35);
                $callback = [
                    Fields::AMOUNT                  => '1.00',
                    Fields::AMOUNT_RULE             => 'EXACT',
                    Fields::MANDATE_TYPE            => 'CREATE',
                    Fields::PAYER_VPA               => $this->fixtures->vpa->getAddress(),
                    Fields::GATEWAY_MANDATE_ID      => $gatewayMandateId,
                    Fields::MERCHANT_CUSTOMER_ID    => $this->fixtures->deviceToken(Fixtures::DEVICE_1)
                                                                      ->getGatewayData()[Fields::MERCHANT_CUSTOMER_ID],
                    Fields::BLOCK_FUND              => true,
                    Fields::GATEWAY_REFERENCE_ID    => '809323430413',
                    Fields::IS_MARKED_SPAM          => 'false',
                    Fields::IS_VERIFIED_PAYEE       => 'true',
                    Fields::INITIATED_BY            => 'PAYEE',
                    Fields::MANDATE_NAME            => 'merchant mandate',
                    Fields::MANDATE_TIMESTAMP       => '2020-06-01T15:40:42+05:30',
                    Fields::MERCHANT_CHANNEL_ID     => 'BANK',
                    Fields::MERCHANT_ID             => 'BANK',
                    Fields::ORG_MANDATE_ID          => 'BJJMsleiuryufhuhsoisdjfadb48003sdaa0',
                    Fields::PAYEE_MCC               => '4121',
                    Fields::PAYEE_NAME              => 'BANKTEST',
                    Fields::PAYEE_VPA               => 'test@bank',
                    Fields::PAYER_REVOCABLE         => 'true',
                    Fields::RECURRENCE_PATTERN      => 'MONTHLY',
                    Fields::RECURRENCE_RULE         => 'ON',
                    Fields::RECURRENCE_VALUE        => '5',
                    Fields::REF_URL                 => 'https://www.abcxyz.com/',
                    Fields::REMARKS                 => 'Sample Remarks',
                    Fields::ROLE                    => 'PAYER',
                    Fields::SHARE_TO_PAYEE          => 'true',
                    Fields::TRANSACTION_TYPE        => 'UPI_MANDATE',
                    Fields::TYPE                    => 'CUSTOMER_INCOMING_MANDATE_CREATE_REQUEST_RECEIVED',
                    Fields::UMN                     => 'b3cecfd8c7654c66af13fc439aca1256@bajaj',
                    Fields::VALIDITY_END            => Carbon::now()->addDays(365)->getTimestamp(),
                    Fields::VALIDITY_START          => Carbon::now()->getTimestamp(),
                ];

                return $callback;
        }
    }

    public function fetchAll(array $content = [])
    {
        $request = $this->request('mandates');

        $this->content($request, [], $content);

        return $this->get($request);
    }

    public function fetch(string $id)
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}';

        $request = $this->request('mandates/%s', [$id]);

        return $this->get($request);
    }

    public function initiateAuthorize(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/authorize/initiate';

        $request = $this->request('mandates/%s/authorize/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function authorizeMandate(string $callback, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/authorize';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateReject(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/reject/initiate';

        $request = $this->request('mandates/%s/reject/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function rejectMandate(string $callback, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/reject';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiatePause(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/pause/initiate';

        $request = $this->request('mandates/%s/pause/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateUnPause(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/unpause/initiate';

        $request = $this->request('mandates/%s/unpause/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateRevoke(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/revoke/initiate';

        $request = $this->request('mandates/%s/revoke/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function pauseMandate(string $callback, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/pause';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function unpauseMandate(string $callback, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/unpause';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function revokeMandate(string $callback, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/revoke';

        $request = $this->request($callback);

        $default = [
            'sdk'   => []
        ];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
