<?php

namespace RZP\Models\BankTransfer\Collectx\Processor;


use Exception;
use Throwable;
use Monolog\Logger;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Core;
use RZP\Models\Merchant\Credits;
use RZP\Models\BankTransfer\Metric;
use RZP\Models\Merchant\Balance\Type;
use RZP\Jobs\ProcessCollectxTransfer;
use RZP\Models\BankTransfer\Processor;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Customer\Balance\Entity;
use RZP\Models\BankTransfer\Collectx\Constants;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankTransfer\Metric as BankTransferMetrics;
use RZP\Models\Ledger\ReverseShadow\Payments as CLSPayments;
use RZP\Models\VirtualAccount\Entity as VirtualAccountEntity;

class Base extends Core
{
    protected $input;

    protected $provider;

    protected $requestPayload;

    public function __construct($input, $provider, $requestPayload)
    {
        parent::__construct();

        $this->input = $input;

        $this->provider = $provider;

        $this->requestPayload = $requestPayload;
    }


    /**
     * @throws Exception
     */
    public function pushToCollectxWorker($input, $provider, $transferRequest, $method): void
    {
        try {
            $dimensions = [
                'provider' => $provider,
                'input' => $input,
                'utr_id' => $transferRequest->getPublicId(),
            ];

            $this->trace->info(TraceCode::COLLECTX_PROCESS_TRANSFER_SQS_PUSH_INIT,
                $dimensions
            );

            ProcessCollectxTransfer::dispatch($this->mode, $input, $provider, $transferRequest->getPublicId());

            $this->trace->info(
                TraceCode::COLLECTX_PROCESS_TRANSFER_JOB_DISPATCHED,
                $dimensions
            );
        }
        catch (Exception $e)
        {
            $this->trace->error(
                ErrorCode::COLLECTX_PROCESS_TRANSFER_SQS_PUSH_ERROR,
                [
                    'error' => $e->getMessage(),
                    'provider' => $provider,
                    'input' => $input,
                    'utr_id' => $transferRequest->getPublicId(),
                ]
            );

            $dimensions = [
                'method'   => $method,
                'provider' => $provider,
            ];

            $this->trace->count(
                Metric::COLLECTX_WORKER_JOB_DISPATCH_FAILURE_COUNT,
                $dimensions
            );

            throw $e;
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateVirtualAccountStatusForCollectxPayments(VirtualAccountEntity $virtualAccount, array $input): void
    {
        if ($virtualAccount->getStatus() === "closed") {

            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_CLOSED_VA,
                $input
            );

        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateTpvForCollectxPayments(VirtualAccountEntity $virtualAccount, array $input, string $provider): void
    {
        if ($provider != Provider::RBL)
        {
            $processor = new Processor();

            $isVerifiedPayer = $processor->verifyPayerUsingTPVForCollectX($virtualAccount, $input);

            if ($isVerifiedPayer === false)
            {
                throw new BadRequestValidationFailureException(
                    ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_BY_NON_ALLOWED_PAYER,
                    $input
                );

            }
        }
    }

    /**
     * @throws BadRequestValidationFailureException|Throwable
     */
    // Only intended to be used for CollectX Payments
    protected function checkForAvailableBalanceAndFeeCredits(string $merchantId): bool
    {
        $availableBalance = $this->getAvailableBalanceForMerchantWithFeeCredits($merchantId);

        if ($availableBalance < Constants::COLLECTX_DEFAULT_FEE_CREDITS_THRESHOLD)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_FEE_CREDITS_BELOW_THRESHOLD,
                $merchantId
            );
        }

        return true;
    }

    /**
     * @throws BadRequestValidationFailureException|Throwable
     */
    protected function getAvailableBalanceForMerchantWithFeeCredits(string $merchantID):int
    {
        /** @var MerchantEntity $merchant */
        $merchant = $this->repo->merchant->getMerchant($merchantID);

        $isMerchantOnCLS = $merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW);

        if($isMerchantOnCLS)
        {
            return $this->getAvailableBalanceForCLSMerchant($merchant);
        }

        return $this->getAvailableBalanceForAPIMerchant($merchant);
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws Throwable
     */
    protected function getAvailableBalanceForCLSMerchant(MerchantEntity $merchant): int
    {
        $ledgerService = $this->app['ledger'];

        $merchantAccountsList = (new CLSPayments\Core())->getMerchantAccounts($ledgerService, $merchant->getId());

        $merchantAccountBalances = (new CLSPayments\Core())->getMerchantAccountBalancesMap($merchantAccountsList);

        $merchantBalance = $merchantAccountBalances[LedgerConstants::MERCHANT_BALANCE];

        $merchantFeeCredits = $merchantAccountBalances[LedgerConstants::MERCHANT_FEE_CREDITS];

        $this->trace->info(TraceCode::COLLECTX_CREDITS_BALANCE_DEBUG, [
            "merchant_id" => $merchant->getId(),
            "merchant_on_cls" => true,
            "balance" => $merchantBalance,
            "fee_credits" => $merchantFeeCredits,
        ]);

        return $merchantBalance + $merchantFeeCredits;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function getAvailableBalanceForAPIMerchant(MerchantEntity $merchant): int
    {
        /** @var Entity $balance */
        $balance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(), Type::PRIMARY);

        if ($balance === null)
        {
            $ex = new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_PRIMARY_BALANCE_UNAVAILABLE_FOR_FEE_CREDITS,
                $merchant->getId()
            );

            $this->trace->traceException(
                $ex,
                Logger::ERROR,
                TraceCode::COLLECTX_PRIMARY_BALANCE_UNAVAILABLE_FOR_FEE_CREDITS, [
                    "merchant_id" => $merchant->getId()
                ]
            );
        }

        /** @var MerchantEntity $merchant */
        $merchant = $this->repo->merchant->getMerchant($merchant->getId());

        $merchantBalance = $balance->getBalance();

        $creditsArray = $this->repo->credits->getTypeAggregatedNonRefundMerchantCreditsWithoutActiveDBTransaction($merchant);

        if (empty($creditsArray) || isset($creditsArray[Credits\Type::FEE]) === false)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_FEE_CREDITS_UNAVAILABLE_FOR_API_MERCHANT,
                $merchant->getId()
            );
        }

        $feeCredits = $creditsArray[Credits\Type::FEE] ?? 0;

        $this->trace->info(TraceCode::COLLECTX_CREDITS_BALANCE_DEBUG, [
            "merchant_id" => $merchant->getId(),
            "merchant_on_cls" => false,
            "balance" => $merchantBalance,
            "fee_credits" => $feeCredits,
        ]);

        return $merchantBalance + $feeCredits;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateBalanceTypeForCollectxPayments(\RZP\Models\Merchant\Balance\Entity $balance, array $input): void
    {
        if ($balance->isTypeBanking() === false || $balance->isAccountTypeDirect() === false) {

            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_ON_NON_DIRECT_OR_NON_BANKING__VA,
                $input
            );

        }
    }

    protected function modifyCollectxResponseBasedOnProvider($valid, string $provider): array
    {
        $response['valid'] = $valid;

        switch ($provider)
        {
            case Provider::YESBANK:
                return [
                    'validateResponse' => [
                        'decision' => $valid ? 'pass' : 'reject'
                    ]
                ];

            case Provider::RBL:
            case Provider::AXIS:
                $response['isCollectXResponse'] = true;

                return $response;
        }

        return $response;
    }

    protected function updateTransferRequest($transferRequest, $ex): void
    {
        if ($transferRequest !== null)
        {
            $transferRequest->setIsCreated(false);

            $transferRequest->setErrorMessage($ex->getMessage());

            $transferRequest->save();
        }
    }

    protected function traceExceptionAndPushUnexpectedPaymentMetric(Exception $ex, array $input, string $provider, $method): void
    {
        $this->trace->traceException(
            $ex,
            Logger::CRITICAL,
            TraceCode::COLLECTX_UNEXPECTED_PAYMENT_TRANSFER_ERROR,
            [
                'input' => $input,
                'provider' => $provider,
                'error_code' => $ex->getCode(),
                'error_message' => $ex->getMessage()
            ]);

        $this->trace->count(
            BankTransferMetrics::COLLECTX_UNEXPECTED_PAYMENT_TRANSFER_COUNT,
            [
                'provider'      => $provider,
                'method'        => $method,
                'error_code'    => $ex->getMessage()
            ]);
    }
}



