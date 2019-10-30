<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever;

use RZP\Reconciliator\RequestProcessor\Retriever\DataRetriever;
use RZP\Reconciliator\RequestProcessor\Retriever\Impl\DefaultDataRetriever;

class DataRetrieverManager
{
    public static function getDataRetriever($gateway): DataRetriever{

        $retriever = studly_case($gateway);

        $gatewayRetrieverClassName = 'RZP\\Reconciliator\\RequestProcessor\\Retriever\\Impl\\' . $retriever . '\\DataRetriever';

        if (class_exists($gatewayRetrieverClassName) === true)
        {
            return new $gatewayRetrieverClassName();
        }
        return new DefaultDataRetriever();
    }
}