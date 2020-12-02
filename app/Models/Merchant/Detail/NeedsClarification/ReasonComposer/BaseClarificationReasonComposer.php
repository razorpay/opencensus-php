<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification\ReasonComposer;

use App;

abstract class BaseClarificationReasonComposer implements ClarificationReasonComposerInterface
{
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }
}