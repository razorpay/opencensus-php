<?php

namespace RZP\Models\Gateway\Rule;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $rule = (new Core)->create($input);

        return $rule->toArrayAdmin();
    }

    public function fetchMultiple(array $input)
    {
        $rules = $this->repo->gateway_rule->fetch($input);

        return $rules->toArrayAdmin();
    }

    public function find(string $id)
    {
        $rule = $this->repo->gateway_rule->findOrFailPublic($id);

        return $rule->toArrayAdmin();
    }

    public function delete(string $id)
    {
        $this->trace->info(
            TraceCode::GATEWAY_LOAD_RULE_DELETE_REQUEST,
            [
                'id' => $id,
            ]);

        $rule = $this->repo->gateway_rule->findOrFailPublic($id);

        $this->repo->deleteOrFail($rule);

        return $rule->toArrayDeleted();
    }

    public function update(string $id, array $input)
    {
        $rule = (new Core)->update($id, $input);

        return $rule->toArrayAdmin();
    }
}
