<?php

namespace RZP\Tests\P2p\Service\Base;

class MandatesHelper extends P2pHelper
{


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

    public function initiateReject(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/{mandate_id}/reject/initiate';

        $request = $this->request('mandate/%s/reject/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function rejectMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/{mandate_id}/reject';

        $request = $this->request('mandate/%s/reject', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiatePause(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/{mandate_id}/pause/initiate';

        $request = $this->request('mandate/%s/pause/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateUnPause(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/{mandate_id}/unpause/initiate';

        $request = $this->request('mandate/%s/unpause/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function initiateRevoke(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/{mandate_id}/revoke/initiate';

        $request = $this->request('mandate/%s/revoke/initiate', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function pauseMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/{mandate_id}/pause';

        $request = $this->request('mandate/%s/pause', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function unpauseMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/{mandate_id}/unpause';

        $request = $this->request('mandate/%s/unpause', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function revokeMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandate/{mandate_id}/revoke';

        $request = $this->request('mandate/%s/revoke', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
