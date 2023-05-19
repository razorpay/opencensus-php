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

    public function fetchStatementForPoolAccount()
    {
        $input = Request::all();

        $channel = $input['channel'];

        switch ($channel)
        {
            case 'rbl':
                $response = $this->service(E::BANKING_ACCOUNT_STATEMENT_POOL_RBL)->fetchStatementForAccount($input);
                break;

            case 'icici':
                $response = $this->service(E::BANKING_ACCOUNT_STATEMENT_POOL_ICICI)->fetchStatementForAccount($input);
                break;

            default:
                $response = ['message' => 'invalid channel. Will throw exception if you hit with same request again'];
        }

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

    public function fetchMissingAccountStatementsForChannel(string $channel)
    {
        $input = Request::all();

        $response = $this->service()->fetchMissingAccountStatementsForChannel($channel, $input);

        return ApiResponse::json($response);
    }

    public function automateAccountStatementsReconByChannel(string $channel)
    {
        $input = Request::all();

        $response = $this->service()->automateAccountStatementsReconByChannel($channel, $input);

        return ApiResponse::json($response);
    }

    public function insertMissingStatements()
    {
        $input = Request::all();

        $response = $this->service()->insertMissingStatements($input);

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

    public function handleMissingStatementUpdateBatchFailure(string $channel)
    {
        $input = Request::all();

        $response = $this->service()->handleMissingStatementUpdateBatchFailure($input + ['channel' => $channel]);

        return ApiResponse::json($response);
    }

    public function insertMissingStatementsAsync($channel)
    {
        $input = Request::all();

        $response = $this->service()->insertMissingStatementsNeo($input + ['channel' => $channel]);

        return ApiResponse::json($response);
    }

    public function detectMissingStatements($channel)
    {
        $input = Request::all();

        $response = $this->service()->detectMissingStatements($input + ['channel' => $channel]);

        return ApiResponse::json($response);
    }
}
