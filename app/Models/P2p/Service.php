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

        $this->core = new P2p\Core;
    }

    public function createP2p($input)
    {
        $p2p = (new Core)->create($input);

        return $p2p->toArrayPublic();
    }

    public function getP2pById($id)
    {
        $p2p = $this->repo->p2p->findOrFail($id);

        return $p2p->toArrayPublic();
    }

    public function getAllP2ps($input)
    {
        $p2ps = $this->repo->p2p->fetch($input);

        return $p2ps->toArrayPublic();
    }
}
