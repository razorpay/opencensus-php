<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\SmartRouting;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $rule = (new Core)->create($input);

        return $rule->toArrayAdmin();
    }

    public function delete(string $id)
    {
        $this->trace->info(
            TraceCode::GATEWAY_RULE_DELETE_REQUEST,
            [
                'id' => $id,
            ]);

        $rule = $this->repo->gateway_rule->findOrFailPublic($id);

        $this->repo->deleteOrFail($rule);

        // try catch added temporarily
        try
        {
            $this->app->smartRouting->deleteGatewayRule($id, $rule->getGroup());
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SMART_ROUTING_SERVICE_ERROR);
        }

        return $rule->toArrayDeleted();
    }

    public function update(string $id, array $input)
    {
        $rule = (new Core)->update($id, $input);

        return $rule->toArrayAdmin();
    }
}
