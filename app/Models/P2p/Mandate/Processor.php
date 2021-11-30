<?php

namespace RZP\Models\P2p\Mandate;

use Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Mandate\Core;
use RZP\Models\P2p\Mandate\Status;
use RZP\Exception\RuntimeException;

/**
 *   * @property Core $core
 */
class Processor extends Base\Processor
{
    public function fetch(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function fetchAll(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function initiateAuthorize(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function authorizeMandate(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function initiateReject(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function rejectMandate(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function initiatePause(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function pauseMandate(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function initiateUnpause(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function unpauseMandate(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function initiateRevoke(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }

    public function revokeMandate(array $input): array
    {
        throw new RuntimeException("Not Implemented , Processor implementation is on the way");
    }
}
