<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use PhpParser\Node\Scalar\String_;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;

class PaginatedDataRetriever extends AbstractAPIDataRetriever
{

    protected $currentPage = 0;
    protected $totalPages = 1;

    const PAGE = 'page';

    protected function getNextRequest(array $input, $prevRequest, $prevResponse): array
    {
        if ((empty($prevResponse) === false) and (isset($prevResponse['data']['total_pages']) === true))
        {
            $this->totalPages = (int) $prevResponse['data']['total_pages'];
        }

        if ($this->currentPage >= $this->totalPages)
        {
            return [];
        }

        $this->currentPage = $this->currentPage + 1;
        $request = [];
        $request[self::GATEWAY] = $input['gateway'];
        $request[self::IDENTIFIER] = (string) $this->currentPage;
        $request[self::START_DATE] = date('Y-m-d', strtotime('-1 days'));
        $request[self::END_DATE] = date('Y-m-d');

        $request[SELF::PAGE] = $this->currentPage;

        if (isset($input['start_date']) === true)
        {
            $request['start_date'] = $input['start_date'];
        }
        if (isset($input['end_date']) === true)
        {
            $request['end_date'] = $input['end_date'];
        }
        if (isset($input['meta_data']) === true)
        {
            $request['meta_data'] = $input['meta_data'];
        }

        return $request;
    }

    protected function refactorResponse(array $responseList): array
    {
        $output = [];
        foreach ($responseList as $key => $value)
        {
            $output[$key] =  $value['data']['records'];
        }
        return $output;
    }
}