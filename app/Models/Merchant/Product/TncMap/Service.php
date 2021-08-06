<?php

namespace RZP\Models\Merchant\Product\TncMap;

use Cache;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($input)
    {
        return $this->core()->create($input);
    }

    public function update($id, $input)
    {
        $tnc = $this->repo->tnc_map->findOrFailPublic($id);

        $tnc = $this->core()->update($tnc, $input);

        return $tnc;
    }

    public function fetch(string $id)
    {
        return $this->core()->fetch($id);
    }

    public function fetchMultiple($input)
    {
        (new Validator)->validateInput('fetch', $input);

        return $this->core()->fetchAll($input);
    }
}
