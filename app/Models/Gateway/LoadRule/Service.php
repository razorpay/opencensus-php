<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $loadRule = (new Core)->create($input);

        return $loadRule->toArrayAdmin();
    }

    public function fetchMultiple(array $input)
    {
        $loadRules = $this->repo->gateway_load_rule->fetch($input);

        return $loadRules->toArrayAdmin();
    }

    public function find(string $id)
    {
        $loadRule = $this->repo->gateway_load_rule->findOrFailPublic($id);

        return $loadRule->toArrayAdmin();
    }

    public function delete(string $id)
    {
        $this->trace->info(
            TraceCode::GATEWAY_LOAD_RULE_DELETE_REQUEST,
            [
                'id' => $id,
            ]);

        $loadRule = $this->repo->gateway_load_rule->findOrFailPublic($id);

        $this->repo->deleteOrFail($loadRule);

        return $loadRule->toArrayAdmin();
    }

    public function update(string $id, array $input)
    {
        $loadRule = (new Core)->update($id, $input);

        return $loadRule->toArrayAdmin();
    }
}
