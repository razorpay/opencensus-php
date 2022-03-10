<?php

namespace RZP\Tests\P2p\Service\Base;

class MandateHelper extends P2pHelper
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

    public function initiateAuthorize(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/authorize/initiate';

        $request = $this->request('mandates/%s/authorize/initiate', [$id]);

        $default = [];

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

    public function rejectMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/reject';

        $request = $this->request('mandates/%s/reject', [$id]);

        $default = [];

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

    public function authorizeMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/authorize';

        $request = $this->request('mandates/%s/authorize', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function pauseMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/pause';

        $request = $this->request('mandates/%s/pause', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function unpauseMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/unpause';

        $request = $this->request('mandates/%s/unpause', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }

    public function revokeMandate(string $id, array $content = [])
    {
        $this->shouldValidateJsonSchema = false;

        $this->validationJsonSchemaPath = 'mandates/{mandate_id}/revoke';

        $request = $this->request('mandates/%s/revoke', [$id]);

        $default = [];

        $this->content($request, $default, $content);

        return $this->post($request);
    }
}
