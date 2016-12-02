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
        $source = $this->setVpaFields($input, 'source');

        $sink = $this->setVpaFields($input, 'sink');

        $p2p = $this->core->create($input);

        $this->eventP2pCreated();

        return $p2p->toArrayPublic();
    }

    protected function setVpaFields(& $input, $field)
    {
        $source = $this->repo->vpa->findByAddressOrFail($input[$field]);

        $input[$field . '_id'] = $source->getPublicId();

        unset($input[$field]);
    }

    public function getById($id)
    {
        Entity::stripSignWithoutValidation($id);

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

    protected function p2pCreated($p2p)
    {
        $this->app['events']->fire('api.p2p.created', array($p2p));
    }

    public function completeAuthorization(string $id, array $input)
    {
        // Checks the MPIN and completes the payment
        $p2p = $this->core->postAuthorize($id, $input);

        return $p2p;
    }
}
