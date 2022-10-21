<?php

namespace App\Trace;

use Session;

class Trace extends TraceWriter
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addRecord($level, $message, array $context = array()): bool
    {
        $traceCode = $message;

        TraceCode::checkCode($traceCode);

        $context = $this->getContext($traceCode, $context);

        return parent::addRecord($level, $traceCode, $context);
    }

    /**
     * Returns context array to be logged with trace record
     *
     * @param array $record
     */
    protected function getContext($code, array $record): array
    {
        $values = array();

        $fields = TraceFields::getFields($code);

        foreach($record as $key => $value)
        {
            $values[$key] = $record[$key];
        }

        $merchant_id = Session::get('current_merchant_id') ?? '';

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $merchant_id = app('request.ctx')->getMerchantId();
        }

        $context = $values + ['merchant_id' => $merchant_id];

        TraceFields::checkFields($code, array_keys($context));

        return $context;
    }
}
