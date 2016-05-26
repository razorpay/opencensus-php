<?php

namespace Reconciliator\HDFC;


use Reconciliator\Base;
use Reconciliator\Messenger;

class Reconciliate extends Base\Reconciliate
{
    protected $messenger;

    public function __construct()
    {
        $this->messenger = new Messenger();
    }
    
    protected function getTypeName($fileName)
    {
        // TODO: Figure out how to get the reconciliation type for HDFC.
        return self::PAYMENT;
    }

    
    public function inExcludeList($fileDetails)
    {
        if (strpos($fileDetails['file_name'], 'detailed') !== false)
        {
            return true;
        }

        return false;
    }
}