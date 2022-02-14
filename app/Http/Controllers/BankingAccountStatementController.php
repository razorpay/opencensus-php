<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;
use RZP\Constants\Entity as E;

class BankingAccountStatementController extends Controller
{
    public function fetchStatementForAccount()
    {
        $input = Request::all();

        $response = $this->service()->fetchStatementForAccount($input);

        return ApiResponse::json($response);
    }

    /**
     * TODO: https://razorpay.atlassian.net/browse/RX-537
     *
     * Expected Input:
     * 'format' : pdf/xlsx/csv
     * 'send_email'  : true/false
     * 'account_number' : '<account_number>',
     * 'channel'        : '<channel>',
     * from_date        : '1568270179'
     * to_date          : '1568270250'
     * @return mixed
     */
    public function generate()
    {
        $input = Request::all();

        $response = $this->service()->requestAccountStatement($input);

        return ApiResponse::json($response);
    }

    public function processAccountStatementForChannel(string $channel)
    {
        $input = Request::all();

        $response = $this->service()->processAccountStatementForChannel($channel, $input);

        return ApiResponse::json($response);
    }

    public function updateSourceLinking()
    {
        $input = Request::all();

        $response = $this->service()->updateSourceLinking($input);

        return ApiResponse::json($response);
    }

    public function validateSourceLinkingUpdate()
    {
        $input = Request::all();

        $response = $this->service()->validateSourceLinkingUpdate($input);

        return ApiResponse::json($response);
    }

    public function createBankingAccountStatementDetails()
    {
        $input = Request::all();

        $response = $this->service(E::BANKING_ACCOUNT_STATEMENT_DETAILS)->create($input);

        return ApiResponse::json($response);
    }
}
