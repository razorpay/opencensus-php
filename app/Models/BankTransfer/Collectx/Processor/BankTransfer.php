<?php

namespace RZP\Models\BankTransfer\Collectx\Processor;

use Throwable;
use Monolog\Logger;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Balance;
use RZP\Models\BankTransfer\Entity;
use RZP\Models\BankTransfer\Validator;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\BankTransferRequest\Core;
use RZP\Models\BankTransfer\Collectx\Constants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankTransfer\Constants as BankTransferConstants;
use RZP\Models\VirtualAccount\Entity as VirtualAccountEntity;

class BankTransfer extends Base
{
    public function __construct($input, $provider, $requestPayload)
    {
        parent::__construct($input, $provider, $requestPayload);
    }

    public function processCollectxTransfer(): array
    {
        $success = true;

        $bankTransferRequest = null;

        try
        {
            $this->trace->info(TraceCode::COLLECTX_BANK_TRANSFER_PAYMENT_PROCESS_REQUEST, [
                    'input'     => $this->input,
                    'provider'  => $this->provider,
                ]);

            $bankTransferRequest = $this->createBankTransferRequestEntity($this->input, $this->provider, $this->requestPayload);

            $this->performValidations($this->input, $this->provider);

            // if current call is validation call for axis or notification callback of mode Transfer, we return response without creating any entity
            if ($this->isAxisValidationOrTransferModeNotificationCallback($this->input, $this->provider) === false)
            {
                $this->pushToCollectxWorker($this->input, $this->provider, $bankTransferRequest, Constants::BANK_TRANSFER);
            }
        }
        catch (BadRequestValidationFailureException $ex)
        {
            $success = false;

            $this->updateTransferRequest($bankTransferRequest, $ex);

            $this->traceExceptionAndPushUnexpectedPaymentMetric($ex, $this->input, $this->provider, Constants::BANK_TRANSFER);
        }
        catch (Throwable $ex)
        {
            $success = false;

            $this->updateTransferRequest($bankTransferRequest, $ex);

            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::COLLECTX_TRANSFER_PROCESSING_ERROR,
                [
                    'input' => $this->input,
                    'provider' => $this->provider,
                    'error_code' => $ex->getCode(),
                    'error_message' => $ex->getMessage()
                ]);
        }

        return $this->modifyCollectxResponseBasedOnProvider($success, $this->provider);
    }

    /**
     * @throws Throwable
     * @throws BadRequestValidationFailureException
     */
    public function performValidations($input, $provider): void
    {
        $this->validateProviderForCollectxBankTransfer($provider, $input);

        $this->validateDuplicateRequest($input);

        $virtualAccount = $this->validateAndGetVirtualAccount($input);

        $this->validateVirtualAccountStatusForCollectxPayments($virtualAccount, $input);

        $balance = $this->repo->balance->findOrFailById($virtualAccount->getBalanceId());

        $this->validateBalanceTypeForCollectxPayments($balance, $input);

        $this->validateCreditAccountNumberForRblCollectxPayments($balance, $provider, $input);

        $this->validateTpvForCollectxPayments($virtualAccount, $input, $provider);

        $this->checkForAvailableBalanceAndFeeCredits($virtualAccount->getMerchantId());
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateCreditAccountNumberForRblCollectxPayments(Balance\Entity $balance, string $provider, array $input): void
    {
        if ($provider === Provider::RBL) {

            $attachedCreditAccountNumber = $balance->getAccountNumber();

            $receivedCreditAccountNumber = $input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER];

            if ($attachedCreditAccountNumber !== $receivedCreditAccountNumber) {

                throw new BadRequestValidationFailureException(
                    ErrorCode::COLLECTX_UNEXPECTED_PAYMENT_CREDIT_ACCOUNT_MISMATCH,
                    $input);

            }
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateAndGetVirtualAccount($input): VirtualAccountEntity
    {
        $accountNumber = $input['payee_account'];

        $ifsc = $input['payee_ifsc'];

        // Check1: Check if bank account and VA exists for the given account number and IFSC
        /* @var VirtualAccountEntity $virtualAccount*/
        $virtualAccount = $this->getVirtualAccountUsingAccountNumberAndIfsc($accountNumber, $ifsc);

        if ($virtualAccount === null)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_VIRTUAL_ACCOUNT_NOT_FOUND,
                $input);
        }

        return $virtualAccount;
    }

    protected function getVirtualAccountUsingAccountNumberAndIfsc(string $accountNumber, string $ifsc)
    {
        $bankAccount = $this->repo
            ->bank_account
            ->findVirtualBankAccountByAccountNumberAndBankCode($accountNumber, $ifsc, true);

        if ($bankAccount === null or $bankAccount->source === null) {
            return null;
        }

        return $bankAccount->source;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateProviderForCollectxBankTransfer(string $provider, array $input): void
    {
        if (in_array($provider, Provider::COLLECTX_BANK_TRANSFER_PROVIDER) === false) {

            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNSUPPORTED_BANK_TRANSFER_REQUEST,
                $input
            );

        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    private function validateDuplicateRequest(array $input): void
    {
        if (!isset($input[Entity::AMOUNT], $input[Entity::REQ_UTR], $input[Entity::PAYEE_ACCOUNT]) === true)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE, $input);
        }

        (new Validator)->validateInput('validateDuplicateReq',
            array(Entity::AMOUNT => $input[Entity::AMOUNT],
            Entity::REQ_UTR => $input[Entity::REQ_UTR],
            Entity::PAYEE_ACCOUNT => $input[Entity::PAYEE_ACCOUNT]));

        $duplicateBankTransfer = $this->repo->bank_transfer->findByCaseInsensitiveUtrAndPayeeAccountAndAmount(
            $input[Entity::REQ_UTR],
            $input[Entity::PAYEE_ACCOUNT],
            $input[Entity::AMOUNT] * 100);

        if ($duplicateBankTransfer !== null)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_DUPLICATE_BANK_TRANSFER_CALLBACK, $input);

        }
    }

    protected function doesRequestBelongToX($input = []): bool
    {
        $payeeIfsc = $input['payee_ifsc'] ?? '';

        if ($payeeIfsc === Provider::getIFSC(true)[Provider::AXIS]) {
            return true;
        }

        return false;
    }

    public function createBankTransferRequestEntity($input, $provider, $requestPayload, $routeName = "bank_transfer_process")
    {
        // need to unset here because input will be used to build BTR and BT entities.
        if (isset($input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER]))
        {
            unset($input[BankTransferConstants::CREDIT_ACCOUNT_NUMBER]);
        }

        $bankTransferRequest = (new Core())->create(
            $input,
            $provider,
            $requestPayload ?? $input,
            routeName: $routeName);

        $this->trace->info(
            TraceCode::COLLECTX_BANK_TRANSFER_REQUEST_CREATED, [
            'input'                 => $input,
            'provider'              => $provider,
            'upi_transfer_request'  => $bankTransferRequest
        ]);

        return $bankTransferRequest;
    }

    protected function isAxisValidationOrTransferModeNotificationCallback(array $input, string $provider): bool
    {
        return $provider === Provider::AXIS &&
            (($input[Entity::REQUEST_TYPE] === Constants::VALIDATION_CALLBACK) ||
                ($input[Entity::REQUEST_TYPE] === Constants::NOTIFICATION_CALLBACK && $input[Entity::MODE] == Constants::TRANSFER_TYPE_TRANSFER));
    }


}
