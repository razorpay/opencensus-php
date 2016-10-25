<?php

namespace RZP\Models\Schedule;

use RZP\Exception\LogicException;
use RZP\Models\Bank;
use RZP\Models\Payment\Method;
use RZP\Models\Card\Network;
use RZP\Models\Base\PublicCollection;

class Collection extends PublicCollection
{
    protected $entity = 'schedule';

    public function updateNextRun()
    {
        $nextSchedules = array_map(function($schedule)
        {
            return $schedule->updateNextRun();

        }, $this->items);

        return $nextSchedules;
    }
}
