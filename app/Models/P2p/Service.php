<?php

namespace RZP\Models\P2p;

use RZP\Exception;
use RZP\Error;
use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create($input)
    {
        $p2p = $this->core->create($input);

        return $p2p->toArrayPublic();
    }

    public function getById($id)
    {
        $p2p = $this->repo->p2p->findOrFail($id);

        return $p2p->toArrayPublic();
    }

    public function getMultiple($input)
    {
        $p2ps = $this->repo->p2p->fetch($input);

        return $p2ps->toArrayPublic();
    }

    public function authorize(string $id, array $input)
    {
        $p2p = $this->core->authorize($id, $input);

        return $p2p;
    }
}
