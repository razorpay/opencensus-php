<?php

namespace RZP\Models\Promotion\Event;

use RZP\Models\Base;
use RZP\Models\Base\Traits\ServiceHasCrudMethods;

class Service extends Base\Service
{
    use ServiceHasCrudMethods;

    protected $entityRepo;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->promotion_event;
    }
}
