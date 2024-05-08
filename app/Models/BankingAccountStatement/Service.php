<?php

namespace RZP\Models\BankingAccountStatement;

use Cache;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Services\PayoutService;
use RZP\Jobs\MissingAccountStatementDetection;
use RZP\Models\BankingAccountStatement\Details as BASD;

class Service extends Base\Service
{
    /**
     * @var PayoutService\BankingAccountStatement
     */
    protected $payoutServiceBankingAccountStatementClient;

    public function __construct()
    {
        parent::__construct();

        $this->payoutServiceBankingAccountStatementClient = $this->app[PayoutService\BankingAccountStatement::PAYOUT_SERVICE_BANKING_ACCOUNT_STATEMENT];
    }

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

        return $this->core()->automateAccountStatementsReconByChannel($channel, $input);
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
            $response[$accountNumber][Constants::UPDATE_MISSING_STATEMENT] = Constants::FAILURE;

            throw $exception;
        }

        $this->trace->info(TraceCode::BAS_ENTITIES_INSERT_AND_BALANCE_UPDATE_DISPATCH_SUCCESS, [
            Entity::CHANNEL => $channel,
        ]);

        return $response;
    }


    public function insertMissingStatementsNeo(array $input, array $updateParams = [])
    {
        $input = $input + [Constants::ACTION => Constants::INSERT];

        (new Validator())->validateInput('insert_statement', $input);

        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        if (isset($updateParams[Entity::MERCHANT_ID]) === false)
        {
            $basDetails = $this->core()->getBasDetails($accountNumber, $channel, [
                BASD\Status::UNDER_MAINTENANCE,
                BASD\Status::ACTIVE
            ]);

            $merchantId = $basDetails->getMerchantId();
        }
        else
        {
            $merchantId = $updateParams[Entity::MERCHANT_ID];
        }

        $missingStatements = $this->core()->getMissingRecordsFromRedisForAccount($accountNumber, $channel, $merchantId);

        $response = $this->core()->insertMissingStatementsNeo($input, $missingStatements, $updateParams);

        $response[$accountNumber][Constants::INSERT_MISSING_STATEMENT] = Constants::SUCCESS;

        $this->trace->info(
            TraceCode::BAS_MISSING_STATEMENTS_INSERTION_ASYNC_SUCCESS,
            [
                Entity::CHANNEL => $channel
            ]);

        return $response;
    }

    public function insertMissingStatements(array $input): array
    {
        (new Validator())->validateInput('insert_statement', $input);

        $accountNumber = $input[Entity::ACCOUNT_NUMBER];

        $channel = $input[Entity::CHANNEL];

        $basCore = $this->core();

        $basDetails = $basCore->getBasDetails($accountNumber, $channel);

        $merchantId = $basDetails[Entity::MERCHANT_ID];

        $missingStatements = $this->core()->getMissingRecordsFromRedisForAccount($accountNumber, $channel, $merchantId);

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
            $response = $basCore->insertMissingStatements($accountNumber, $channel, $merchantId, $missingStatements, $dryRunMode);

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
                    Entity::CHANNEL        => $channel,
                    Entity::MERCHANT_ID    => $merchantId
                ]
            );

            if ($exception->getCode() !== ErrorCode::BAD_REQUEST_ANOTHER_BANKING_ACCOUNT_STATEMENT_FETCH_IN_PROGRESS)
            {
                $this->trace->count(Metric::MISSING_STATEMENT_INSERT_FAILURE, [
                    Metric::LABEL_CHANNEL => $channel,
                ]);

                $this->core()->releaseBasDetailsFromStatementFix($accountNumber, $channel);
            }

            $this->trace->count(Metric::INSERT_AND_UPDATE_BAS_FAILURE);

            throw $exception;
        }
    }

    public function handleMissingStatementUpdateBatchFailure(array $input): array
    {
         $this->trace->info(TraceCode::BAS_MISSING_STATEMENT_BALANCE_UPDATE_TRIGGER_REQUEST, $input);

        return $this->core()->handleMissingStatementUpdateBatchFailure($input);
    }

    public function detectMissingStatements(array $input): array
    {
        $this->trace->info(TraceCode::BAS_MISSING_STATEMENTS_DETECTION_REQUEST, $input);

        (new Validator())->validateInput(Validator::DETECT_MISSING_STATEMENTS, $input);

        $channel = $input[Entity::CHANNEL];

        $accountNumberList = array_unique($input[Constants::ACCOUNT_NUMBERS]);

        $suspectedMismatchTimestamp = $input[Constants::SUSPECTED_MISMATCH_TIMESTAMP] ?? null;

        $dispatchedAccountNumberList = [];

        if (isset($suspectedMismatchTimestamp) === false)
        {
            $suspectedMismatchTimestamp = Carbon::now(Timezone::IST)->timestamp;
        }

        foreach ($accountNumberList as $accountNumber)
        {
            try
            {
                // reset missing statement detection config for the account number
                $this->core()->updateMissingStatementConfigFor($accountNumber, $channel, []);

                MissingAccountStatementDetection::dispatch($this->mode, $accountNumber, null, $suspectedMismatchTimestamp, $channel);

                $dispatchedAccountNumberList[] = $accountNumber;
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    null,
                    TraceCode::BAS_MISSING_STATEMENTS_DETECTION_DISPATCH_FAILURE,
                    [
                        Entity::ACCOUNT_NUMBER                  => $accountNumber,
                        Entity::CHANNEL                         => $channel,
                        Constants::SUSPECTED_MISMATCH_TIMESTAMP => $suspectedMismatchTimestamp
                    ]
                );
            }
        }

        $jobDispatchSummary = [
            Entity::CHANNEL                         => $channel,
            Constants::ACCOUNT_NUMBERS              => $dispatchedAccountNumberList,
            Constants::SUSPECTED_MISMATCH_TIMESTAMP => $suspectedMismatchTimestamp
        ];

        $this->trace->info(TraceCode::BAS_MISSING_STATEMENTS_DETECTION_SUMMARY, $jobDispatchSummary);

        return $jobDispatchSummary;
    }

    public function processStatementPostRecon($input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_POST_RECON_REQUEST, $input);

        try
        {
            $this->payoutServiceBankingAccountStatementClient->triggerStatementProcessingPostReconViaMicroService($input);
        }
        catch (\Exception $exception)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_POST_RECON_FAILURE,
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESS_POST_RECON_RESPONSE, $input);

        return [
            "success" => true
        ];
    }

    public function handleBasAdminActions($input)
    {
        $action = $input['action'];

        switch ($action)
        {
            case 'ps_bas_link':
                $input['banking_account_statement_id'] = $input[Entity::ID];
                unset($input[Entity::ID]);
                unset($input[Entity::MERCHANT_ID]);
                unset($input['balance_id']);
                unset($input['action']);

                $this->linkStatementInPayoutService($input);

                break;

        }

        return ['success' => $input];
    }

    public function linkStatementInPayoutService($input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_LINK_REQUEST, $input);

        try
        {
            $this->payoutServiceBankingAccountStatementClient->devAdminTriggerLinkViaMicroService($input);
        }
        catch (\Exception $exception)
        {
            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_LINK_FAILURE,
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }

        $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_LINK_RESPONSE, $input);
    }
}
