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
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_REQUEST,
            [
                'input' => $input
            ]);

        return $this->core()->requestAccountStatement($input);
    }

    public function processAccountStatementForChannel(string $channel, array $input)
    {
        $response = $this->core()->dispatchAccountNumberForChannel($channel, $input);

        return $response;
    }

    public function fetchMissingAccountStatementsForChannel(string $channel, array $input)
    {
        (new Validator())->validateInput(Validator::FETCH_MISSING_STATEMENTS, $input + ['channel' => $channel]);

        $response = $this->core()->fetchMissingAccountStatementsForChannel($channel, $input);

        return $response;
    }

    public function updateSourceLinking(array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_SOURCE_LIKING_UPDATE_REQUEST,
            [
                'input' => $input
            ]);

        $response =  $this->core()->updateSourceLinking($input);

        return $response;
    }

    /**
     * On admin dashboard, we will be displaying all the necessary details needed for source linking as discovered
     * based upon input provided after some validations.
     */
    public function validateSourceLinkingUpdate(array $input)
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_SOURCE_LIKING_UPDATE_VALIDATION_REQUEST,
            [
                'input' => $input
            ]);

        $validator = new Validator;

        $validator->validateInput(Validator::SOURCE_UPDATE, $input);

        /* @var \RZP\Models\Payout\Entity $payout */
        $payout = $this->repo->payout->findOrFail($input['payout_id']);

        $current_status = $payout->getStatus();

        $validator->validateCreditBas($current_status, $input);

        $debit_bas = $this->repo->banking_account_statement->findOrFail($input['debit_bas_id']);

        $credit_bas = null;

        if (isset($input['credit_bas_id']) === true)
        {
            $credit_bas = $this->repo->banking_account_statement->findOrFail($input['credit_bas_id']);
        }

        $response = [
            'payout'     => $payout->toArrayPublic(),
            'debit_bas'  => $debit_bas->toArrayPublic(),
            'credit_bas' => optional($credit_bas)->toArrayPublic(),
        ];

        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_STATEMENT_SOURCE_LIKING_UPDATE_VALIDATION_RESPONSE,
            [
                'payout'     => $response['payout'],
                'debit_bas'  => $response['debit_bas'],
                'credit_bas' => $response['credit_bas'],
                'end_status' => $input['end_status']
            ]);

        return $response;
    }
}
