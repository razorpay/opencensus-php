<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;

/**
 * @property  Processor $processor
 */
class Service extends Base\Service
{
    public function fetch(array $input): array
    {
        $response = $this->processor->fetch($input);

        return $response;
    }

    public function fetchAll(array $input): array
    {
        $response = $this->processor->fetchAll($input);

        return $response;
    }

    public function initiateAuthorize(array $input): array
    {
        $response = $this->processor->initiateAuthorize($input);

        return $response;
    }

    public function authorizeMandate(array $input): array
    {
        $response = $this->processor->authorizeMandate($input);

        return $response;
    }

    public function initiateReject(array $input): array
    {
        $response = $this->processor->initiateReject($input);

        return $response;
    }

    public function rejectMandate(array $input): array
    {
        $response = $this->processor->rejectMandate($input);

        return $response;
    }

    public function initiatePause(array $input): array
    {
        $response = $this->processor->initiatePause($input);

        return $response;
    }

    public function pauseMandate(array $input): array
    {
        $response = $this->processor->pauseMandate($input);

        return $response;
    }

    public function initiateUnPause(array $input): array
    {
        $response = $this->processor->initiateUnPause($input);

        return $response;
    }

    public function unpauseMandate(array $input): array
    {
        $response = $this->processor->unpauseMandate($input);

        return $response;
    }

    public function initiateRevoke(array $input): array
    {
        $response = $this->processor->initiateRevoke($input);

        return $response;
    }

    public function revokeMandate(array $input): array
    {
        $response = $this->processor->revokeMandate($input);

        return $response;
    }
}
