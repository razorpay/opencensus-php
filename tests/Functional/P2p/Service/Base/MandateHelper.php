<?php

namespace RZP\Tests\P2p\Service\Base;

use Carbon\Carbon;
use RZP\Gateway\P2p\Upi\Sharp\Fields;
use RZP\Gateway\P2p\Upi\Sharp\Actions\UpiAction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

class MandateHelper extends P2pHelper
{

    public function createMandate($gateway)
    {
        $request = [
            Fields::TYPE                    => UpiAction::INCOMING_MANDATE_CREATE,
            Fields::AMOUNT                  => 100,
            Fields::AMOUNT_RULE             => 'MAX',
            Fields::PAYER_VPA               => $this->fixtures->vpa(Fixtures::DEVICE_1)->getAddress(),
            Fields::PAYEE_VPA               => 'username@randompsp',
            Fields::VALIDITY_START          => Carbon::now()->getTimestamp(),
            Fields::VALIDITY_END            => Carbon::now()->addDays(365)->getTimestamp(),
            Fields::TRANSACTION_NOTE        => 'UPI',
            Fields::RECUR                   => 'DAILY',
        ];

        $content = [
            'content' => json_encode($request)
        ];

        $response = $this->callback($gateway, $content);

        return $response;
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
