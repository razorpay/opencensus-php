<?php

namespace RZP\Reconciliator\RequestProcessor\Retriever\Impl;

use App;
use RZP\Trace\TraceCode;

class PaginatedDataRetriever extends DefaultDataRetriever
{

    protected $currentPage = 1;
    protected $totalPages = 1;

    const PAGE        = 'page';
    const TOTAL_PAGES = 'total_pages';

    protected function getNextRequest(array $input, $prevRequest, $prevResponse): array
    {
        if ((empty($prevResponse) === false) and (isset($prevResponse['data'][SELF::TOTAL_PAGES]) === true))
        {
            $this->totalPages = (int) $prevResponse['data'][SELF::TOTAL_PAGES];
        }

        if ($this->currentPage > $this->totalPages)
        {
            return [];
        }

        $request = [];

        $request[self::GATEWAY] = $input[self::GATEWAY];
        $request[self::IDENTIFIER] = (string) $this->currentPage;

        $request[self::START_DATE] = date('Y-m-d', strtotime('-1 days'));
        $request[self::END_DATE] = date('Y-m-d');

        $request[SELF::PAGE] = $this->currentPage;

        if (isset($input[self::START_DATE]) === true)
        {
            $request[self::START_DATE] = $input[self::START_DATE];
        }

        if (isset($input[self::END_DATE]) === true)
        {
            $request[self::END_DATE] = $input[self::END_DATE];
        }

        if (isset($input['meta_data']) === true)
        {
            $request['meta_data'] = $input['meta_data'];
        }

        $this->currentPage = $this->currentPage + 1;

        return $request;
    }
}