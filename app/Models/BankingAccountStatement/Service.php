<?php

namespace RZP\Models\BankingAccountStatement;

use Cache;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Constants\Mode as EnvMode;

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

    public function automateAccountStatementsReconByChannel(string $channel, array $input)
    {
        (new Validator())->validateInput(Validator::AUTOMATE_ACCOUNT_STATEMENT_RECON, $input + ['channel' => $channel]);

        $this->trace->info(
            TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_INITIATED,
            [
                Entity::CHANNEL                    => $channel,
                Constants::ACCOUNT_NUMBERS_PRESENT => count($input[Constants::ACCOUNT_NUMBERS]),
            ]);

        $attempts = [];

        $response = [];

        $isNewCron = ((isset($input['new_cron_setup']) === true) && ($input['new_cron_setup'] === true));

        $isMonitoringCron = ((isset($input['monitoring_cron']) === true) && ($input['monitoring_cron'] === true));

        $maxExpectedAttempts = 0;

        foreach ($input[Constants::ACCOUNT_NUMBERS] as $accountNumber)
        {
            try
            {
                if (($isNewCron === true) or ($isMonitoringCron === true))
                {
                    $fetchInput = [
                        Entity::CHANNEL => $channel,
                        Entity::ACCOUNT_NUMBER => $accountNumber,
                        Entity::FROM_DATE => Carbon::now(Timezone::IST)->subDay()->startOfDay()->getTimestamp(),
                        Entity::TO_DATE => Carbon::now(Timezone::IST)->subDay()->endOfDay()->getTimestamp(),
                        Entity::SAVE_IN_REDIS => $input[Entity::SAVE_IN_REDIS] ?? true
                    ];
                }
                else
                {
                    $fetchInput = [
                        Entity::CHANNEL => $channel,
                        Entity::ACCOUNT_NUMBER => $accountNumber,
                        Entity::FROM_DATE => Carbon::now(Timezone::IST)->startOfDay()->getTimestamp(),
                        Entity::TO_DATE => Carbon::now(Timezone::IST)->endOfDay()->getTimestamp(),
                        Entity::SAVE_IN_REDIS => $input[Entity::SAVE_IN_REDIS] ?? true
                    ];
                }

                $attempts[$accountNumber] = $this->core()->fetchMissingAccountStatementsForChannel($channel, $fetchInput, $isNewCron, $isMonitoringCron)[Constants::EXPECTED_ATTEMPTS];

                $maxExpectedAttempts = max($maxExpectedAttempts, $attempts[$accountNumber][Constants::EXPECTED_ATTEMPTS]);

                $response[$accountNumber][Constants::FETCH_MISSING_STATEMENT] = Constants::SUCCESS;
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    null,
                    TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FETCH_DISPATCH_FAILED,
                    [
                        Entity::ACCOUNT_NUMBER => $accountNumber,
                        Entity::CHANNEL        => $channel
                    ]
                );

                $response[$accountNumber][Constants::FETCH_MISSING_STATEMENT] = Constants::FAILURE;
            }
        }

        $this->trace->info(
            TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_FETCH_DISPATCH_SUCCESS,
            [
                Entity::CHANNEL => $channel,
                'environment'   => $this->app->environment('testing'),
                'mode'          => $this->mode
            ]);

        if ($isNewCron === true)
        {
             return $response;
        }

        foreach ($attempts as $accountNumber => $expectedAttempts)
        {
            try
            {
                // We have added 80 secs as request timeout for mozart request, and since there can be 2 retries
                // setting the delay as 3 mins
                $updateInput = [
                    Entity::CHANNEL        => $channel,
                    Entity::ACCOUNT_NUMBER => (string) $accountNumber,
                    Constants::ACTION      => $input[Constants::ACTION] ?? Constants::INSERT
                ];

                $this->insertMissingStatements($updateInput);

                $response[$accountNumber][Constants::UPDATE_MISSING_STATEMENT] = Constants::SUCCESS;
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    null,
                    TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_UPDATE_DISPATCH_FAILED,
                    [
                        Entity::ACCOUNT_NUMBER => $accountNumber,
                        Entity::CHANNEL        => $channel
                    ]
                );

                $response[$accountNumber][Constants::UPDATE_MISSING_STATEMENT] = Constants::FAILURE;
            }
        }

        $this->trace->info(
            TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_UPDATE_DISPATCH_SUCCESS,
            [
                Entity::CHANNEL => $channel,
            ]);

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

    public function insertMissingStatementsAndProcessNeo(array $input)
    {
        try
        {
            $accountNumber = $input['account_number'];

            $channel = $input['channel'];

            $updateInput = [
                Entity::CHANNEL        => $channel,
                Entity::ACCOUNT_NUMBER => (string)$accountNumber,
                Constants::ACTION      => $input[Constants::ACTION] ?? Constants::INSERT
            ];

            $this->insertMissingStatements($updateInput);

            $response[$accountNumber][Constants::UPDATE_MISSING_STATEMENT] = Constants::SUCCESS;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_UPDATE_DISPATCH_FAILED,
                [
                    Entity::ACCOUNT_NUMBER => $accountNumber,
                    Entity::CHANNEL => $channel
                ]
            );

            $response[$accountNumber][Constants::UPDATE_MISSING_STATEMENT] = Constants::FAILURE;
        }

        $this->trace->info(
            TraceCode::AUTOMATED_ACCOUNT_STATEMENTS_RECON_UPDATE_DISPATCH_SUCCESS,
            [
                Entity::CHANNEL => $channel,
            ]);

        return $response;
    }

    public function insertMissingStatements(array $input): array
    {
        (new Validator())->validateInput('insert_statement', $input);

        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        $missingStatements = $this->core()->getMissingRecordsFromRedisForAccount($accountNumber, $channel);

        if ($input[Constants::ACTION] === Constants::FETCH)
        {
            return [
                'number_of_missing_statements' => count($missingStatements),
                'missing_statements'           => json_encode($missingStatements),
            ];
        }

        if (count($missingStatements) === 0)
        {
            return [
                'message' => 'No missing BAS statements to insert for the given account number'
            ];
        }

        $dryRunMode = false;

        if ($input[Constants::ACTION] === Constants::DRY_RUN)
        {
            $dryRunMode = true;
        }

        try
        {
            $response = $this->core()->insertMissingStatements($accountNumber, $channel, $missingStatements, $dryRunMode);

            return $response;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::INSERT_AND_UPDATE_BAS_FAILURE,
                [
                    Entity::ACCOUNT_NUMBER => $accountNumber,
                    Entity::CHANNEL        => $channel
                ]
            );

            if ($exception->getCode() !== ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS)
            {
                $this->trace->count(Metric::MISSING_STATEMENT_INSERT_FAILURE, [
                    Metric::LABEL_CHANNEL => $channel,
                ]);

                $this->core()->releaseBasDetailsFromStatementFix($accountNumber, $channel);
            }

            throw $exception;
        }
    }
}
