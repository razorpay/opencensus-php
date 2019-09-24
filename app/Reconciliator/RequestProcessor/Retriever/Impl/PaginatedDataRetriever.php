<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;

class PaginatedDataRetriever extends AbstractAPIDataRetriever
{

    protected $currentPage = 0;
    protected $totalPages = 1;

    protected function getNextRequest(array $input, $prevRequest, $prevResponse): array
    {
        if(!empty($prevResponse) and isset($prevResponse['data']['total_pages']))
        {
            $this->totalPages = (int) $prevResponse['data']['total_pages'];
        }

        if($this->currentPage >= $this->totalPages)
        {
            return [];
        }
        $this->currentPage = $this->currentPage + 1;
        $request = [];
        $request[self::GATEWAY] = $input['gateway'];
        $request[self::IDENTIFIER] = "_";
        $request['page'] = $this->currentPage;
        if(isset($input['start_date']))
        {
            $request['start_date'] = $input['start_date'];
        }
        else
        {
            $request['start_date'] = date('Y-m-d', strtotime("-1 days"));
        }
        if(isset($input['end_date']))
        {
            $request['end_date'] = $input['end_date'];
        }
        else
        {
            $request['end_date'] = date('Y-m-d');
        }
        if(isset($input['meta_data']))
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