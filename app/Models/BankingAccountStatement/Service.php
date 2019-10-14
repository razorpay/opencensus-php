<?php

namespace RZP\Models\BankingAccountStatement;

use Cache;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    public function fetchStatementForAccount(array $input): array
    {
        $response = $this->core()->processStatementForAccount($input);

        return $response;
    }

    public function requestAccountStatement(array $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_REQUEST,
                           ['input' => $input]);
        try
        {
            return $this->core()->requestAccountStatement($input);
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e,
                                         Trace::ERROR,
                                         TraceCode::BANKING_ACCOUNT_STATEMENT_REQUEST,
                                         $input);
            throw $e;
        }
    }
}
