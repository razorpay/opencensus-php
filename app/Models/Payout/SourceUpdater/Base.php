<?php

namespace RZP\Models\Payout\SourceUpdater;

use App;
use RZP\Models\Payout\Entity as PayoutEntity;

abstract class Base
{
    protected $payout;

    protected $mode;

    protected $app;

    public function __construct(PayoutEntity $payout, string $mode)
    {
        $this->payout = $payout;

        $this->mode = $mode;

        $this->app = App::getFacadeRoot();
    }

    abstract function update();
}
