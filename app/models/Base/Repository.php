<?php

namespace Models\Base;

use Constants\Entity as E;
use Constants\Table;
use DB;
use Illuminate\Support\Facades\App;
use Trace\TraceCode;

class Repository extends \Base\Repository
{
    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        return $this->fetchBetweenTimestampWithRelations($merchantId, $from, $to);
    }
}