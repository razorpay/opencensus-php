<?php

namespace RZP\Reconciliator\Base;

class ManualReconciliate extends Reconciliate
{
    public function getTypeName($fileName)
    {
        return self::MANUAL;
    }
}
