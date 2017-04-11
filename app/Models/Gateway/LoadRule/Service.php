<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;

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
        $loadRule = $this->repo->gateway_load_rule->findOrFailPublic($id);

        $this->repo->deleteOrFail($loadRule);

        return $loadRule->toArrayAdmin();
    }
}
