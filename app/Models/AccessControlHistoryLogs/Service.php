<?php

namespace RZP\Models\AccessControlHistoryLogs;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $entityRepo;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create(array $input) :array
    {
        return $this->core->create($input);
    }
}
