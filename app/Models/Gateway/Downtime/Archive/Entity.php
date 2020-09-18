<?php

namespace RZP\Models\Gateway\Downtime\Archive;

use RZP\Constants\Table;
use RZP\Models\Gateway\Downtime\Entity as Downtime;

class Entity extends Downtime {
    public $timestamps = false;

    protected $entity = 'gateway_downtime_archive';

    protected $table = Table::GATEWAY_DOWNTIME_ARCHIVE;

    protected $generateIdOnCreate = false;
}
