<?php

namespace RZP\Models\Payee;

use RZP\Models\Base;

/**
 * Class Service
 *
 * @package RZP\Models\Payee
 */
class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var Repository
     */
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->payee;
    }
}
