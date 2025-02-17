<?php

namespace RZP\Models\BankTransfer\Collectx\Processor;

use Throwable;
use Monolog\Logger;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer\Entity;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\BankTransfer\Collectx\Constants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\UpiTransfer\Service as UpiTransferService;
use RZP\Models\VirtualAccount\Entity as VirtualAccountEntity;
use RZP\Models\UpiTransferRequest\Service as UpiTransferRequestService;

class UpiTransfer extends Base{
    public function __construct($input, $provider, $requestPayload)
    {
        parent::__construct($input, $provider, $requestPayload);
    }

    public function processCollectxTransfer(): array
    {
        $success = true;

        $upiTransferRequest = null;

        try
        {
            $this->trace->info(TraceCode::COLLECTX_UPI_TRANSFER_PAYMENT_PROCESS_REQUEST, [
                    'input'     => $this->input,
                    'provider'  => $this->provider,
                ]);

            $upiTransferRequest = $this->createUpiTransferRequestEntity($this->input, $this->provider, $this->requestPayload);

            $this->performValidations($this->input, $this->provider);

            $this->pushToCollectxWorker($this->input, $this->provider, $upiTransferRequest, Constants::UPI_TRANSFER);
        }
        catch (BadRequestValidationFailureException $ex)
        {
            $success = false;

            $this->updateTransferRequest($upiTransferRequest, $ex);

            $this->traceExceptionAndPushUnexpectedPaymentMetric($ex, $this->input, $this->provider, Constants::UPI_TRANSFER);
        }
        catch (Throwable $ex)
        {
            $success = false;

            $this->updateTransferRequest($upiTransferRequest, $ex);

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
        $virtualAccount = $this->validateAndGetVirtualAccount($input);

        $this->validateProviderForCollectxUPI($provider, $input);

        $this->validateDuplicateUpiRequest($input);

        $this->validateVirtualAccountStatusForCollectxPayments($virtualAccount, $input);

        $this->validateTpvForCollectxPayments($virtualAccount, $input, $provider);

        $this->checkForAvailableBalanceAndFeeCredits($virtualAccount->getMerchantId());
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    protected function validateAndGetVirtualAccount($input)
    {
        $virtualAccount = $this->getVirtualAccountUsingVpaAddress($input[VirtualAccountEntity::PAYEE_ACCOUNT], ProviderCode::YESBANKLTD);

        if ($virtualAccount === null)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_VIRTUAL_ACCOUNT_NOT_FOUND,
                $input);
        }

        return $virtualAccount;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    // Only intended to be used for CollectX Payments
    protected function validateDuplicateUpiRequest(array $input): void
    {
        $providerReferenceId = $input['transaction_id'];

        // Use provider to get provider code when live with more than just Yesbank for UPI
        $payeeVpa = $input["payee_account"] . "@" . ProviderCode::YESBANKLTD;

        $amount = $input["amount"] * 100;

        $upiTransferEntity = $this->repo->upi_transfer->findByProviderReferenceIdAndPayeeVpaAndAmount(
            $providerReferenceId,
            $payeeVpa,
            $amount);

        if ($upiTransferEntity !== null) {

            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_DUPLICATE_UPI_TRANSFER_REQUEST,
                $input
            );

        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateProviderForCollectxUPI(string $provider, array $input): void
    {
        if (in_array($provider, Provider::COLLECTX_UPI_PROVIDERS) === false) {

            throw new BadRequestValidationFailureException(
                ErrorCode::COLLECTX_UNSUPPORTED_UPI_TRANSFER_REQUEST,
                $input
            );

        }
    }

    public function createUpiTransferRequestEntity($input, $provider, $requestPayload): ?\RZP\Models\UpiTransferRequest\Entity
    {
        $upiTransferData = (new UpiTransferService())->formatGatewayResponseDataForCollectX($input, $provider);

        $upiTransferRequest = (new UpiTransferRequestService())->create($upiTransferData, $requestPayload);

        return $upiTransferRequest;
    }

    protected function getVirtualAccountUsingVpaAddress(string $username, string $handle)
    {
        $payeeVpa = $username."@".$handle;

        $vpa = $this->repo
            ->vpa
            ->findByAddressAndEntityTypes($payeeVpa, [Entity::VIRTUAL_ACCOUNT], true);

        if ($vpa === null || $vpa->source === null) {
            return null;
        }

        return $vpa->source;
    }


}
