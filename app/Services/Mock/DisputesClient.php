<?php

namespace RZP\Services\Mock;

use RZP\Constants\Table;
use \WpOrg\Requests\Response;
use RZP\Tests\Functional\Dispute\DisputeTest;

class DisputesClient
{
    /**
     * {@inheritDoc}
     */
    public function request(string $path, array $payload, int $timeoutMs = null)
    {
        $res = new \WpOrg\Requests\Response;
        $res->success = true;
        $res->body = '{}';

        return $res;
    }

    public function forwardToDisputesService()
    {
        return [];
    }

    public function isShadowModeDualWrite($route)
    {
        return false;
    }


    public function sendDualWriteToDisputesService($entityData, $table, $action)
    {
        (new DisputeTest())->assertDualWriteDisputeEntityById($table, $entityData, $action);
    }
}
