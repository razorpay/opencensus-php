<?php

namespace RZP\Services\FTS;

use RZP\Exception\LogicException;
use RZP\Models\Vpa\Core as VPACore;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Payment\Core as PaymentCore;
use RZP\Models\Payment\Refund\Core as RefundCore;
use RZP\Models\Settlement\Core as SettlementCore;
use RZP\Models\BankAccount\Core as BankAccountCore;
use RZP\Models\FundTransfer\Attempt\Core as FundTransferAttemptCore;
use RZP\Models\FundAccount\Validation\Core  as FundAccountValidationCore;

class FundTransfer extends Base
{
    protected $FTACore;

    protected $fta;

    protected $source;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->FTACore = new FundTransferAttemptCore;
    }

    public function requestFundTransfer(string $ftaId, string $accountType, bool $isRegistered):array
    {
        $input = $this->makeRequestUsingType($ftaId, $accountType, $isRegistered);

        $response = $this->createAndSendRequest(
            parent::FUND_TRANSFER_BASE_URL . '/' . parent::URLS['request'],
            'POST', $input);

        $this->handleResponse($response['body'], $accountType);

        return $response;
    }

    public function makeRequestUsingType(string $ftaId, string $type, bool $isRegistered):array
    {
        $this->fta = $this->FTACore->getFTAEntity($ftaId);

        $sourceId   = $this->fta->getSourceId();

        $sourceType = $this->fta->getSourceType();

        $request = [
            //TODO:: Derive product name using source_type and purpose (e.g in case of payout-refund)
            Constants::PRODUCT           => $sourceType,
            Constants::MERCHANT_ID       => $this->fta->merchant->getId(),
        ];

        $request = $this->addTransferBlock($request, $sourceId, $sourceType);

        if($isRegistered === true)
        {
            $request = $this->addFTSFundAccountId($request);
        }
        else
        {
                switch ($type)
                {
                    case Constants::BANK_ACCOUNT:
                        $request = $this->addBankAccountDetails($request);

                        break;

                    case Constants::VPA:
                        $request = $this->addVpaDetails($request);

                        break;

                    default:
                        throw new LogicException('Account Type is not supported ' . $type);

                }
        }

        return $request;
    }

    protected function addTransferBlock(
        array $request,
        string $sourceId,
        string $sourceType):array
    {
        $this->setSourceEntityByType($sourceId, $sourceType);

        $request[Constants::TRANSFER] = [
            Constants::MODE              => $this->fta->getMode(),
            Constants::AMOUNT            => $this->source->getAmount(),
            Constants::CHANNEL           => $this->fta->getChannel(),
            Constants::NARRATION         => $this->fta->getNarration(),
            Constants::INITIATE_AT       => $this->fta->getInitiateAt(),
        ];

        return $request;
    }

    public function addFTSFundAccountId(array $request):array
    {
        $request[Constants::ACCOUNT] = array(
            Constants::FUND_ACCOUNT_ID   => $this->fta->bankAccount->getFtsFundAccountId(),
        );

        return $request;
    }

    public function addBankAccountDetails(array $request):array
    {
        $request[Constants::ACCOUNT] = [

                Constants::BANK_ACCOUNT => [
                        Constants::IFSC_CODE                  => $this->fta->bankAccount->getIfscCode(),
                        Constants::ACCOUNT_TYPE               => $this->fta->bankAccount->getAccountType(),
                        Constants::ACCOUNT_NUMBER             => $this->fta->bankAccount->getAccountNumber(),
                        Constants::BENEFICIARY_NAME           => $this->fta->bankAccount->getBeneficiaryName(),
                        Constants::BENEFICIARY_CITY           => $this->fta->bankAccount->getBeneficiaryCity(),
                        Constants::BENEFICIARY_EMAIL          => $this->fta->bankAccount->getBeneficiaryEMail(),
                        Constants::BENEFICIARY_STATE          => $this->fta->bankAccount->getBeneficiaryState(),
                        Constants::BENEFICIARY_MOBILE         => $this->fta->bankAccount->getBeneficiaryMobile(),
                        Constants::IS_VIRTUAL_ACCOUNT         => $this->fta->bankAccount->isVirtual(),
                        Constants::BENEFICIARY_ADDRESS        => $this->fta->bankAccount->getBeneficiaryAddress1(),
                        Constants::BENEFICIARY_COUNTRY        => $this->fta->bankAccount->getBeneficiaryCountry(),
                ],
        ];

        return $request;
    }

    public function addVpaDetails(array $request):array
    {
        $request[Constants::ACCOUNT] = [

                Constants::VPA => [
                        Constants::HANDLE       => $this->fta->vpa->getHandle(),
                        Constants::USERNAME     => $this->fta->vpa->getUsername(),
                ],
        ];

        return $request;
    }

    protected function setSourceEntityByType(string $sourceId, string $sourceType)
    {
        switch ($sourceType)
        {
            case Constants::SETTLEMENT:
                $this->source = (new SettlementCore)->getSettlementEntityById($sourceId);

                break;

            case Constants::REFUND:
                $this->source = (new PaymentCore)->retrieveRefundById($sourceId);

                break;

            case Constants::PAYOUT:
                $this->source = (new PayoutCore)->getPayoutEntityById($sourceId);

                break;

            case Constants::FUND_ACCOUNT_VALIDATION:
                $this->source = (new FundAccountValidationCore)->getFundAccountValidationEntityById($sourceId);

                break;

            default:
                throw new LogicException('Source Type is not supported : ' . $sourceType);

        }
    }

    /**
     * Method to update entities using
     * received response.
     *
     * @param array $responseBody
     * @param string $type
     */
    protected function handleResponse(array $responseBody, string $type)
    {
        $this->updateFTA($responseBody);

        $this->updatePaymentInstrumentByType($responseBody, $type);
    }

    protected function updateFTA(array $responseBody)
    {
        $ftsTransferId = $responseBody[Constants::FUND_TRANSFER_ID];

        $this->FTACore->updateFTA($this->fta, $ftsTransferId, $responseBody['status']);

        $this->updateSource($ftsTransferId);
    }

    protected function updatePaymentInstrumentByType(array $responseBody, string $type)
    {
        switch ($type)
        {
            case Constants::BANK_ACCOUNT:
                (new BankAccountCore)->updateBankAccountWithFtsId(
                    $this->fta->bankAccount,
                    $responseBody[Constants::FUND_ACCOUNT_ID]);

                break;

            case Constants::VPA:
                (new VPACore)->updateVpaWithFtsId($this->fta->vpa, $responseBody[Constants::FUND_ACCOUNT_ID]);

                break;
        }
    }

    protected function updateSource($ftsTransferId)
    {
        switch ($this->fta->getSourceType())
        {
            case Constants::SETTLEMENT:
                (new SettlementCore)->updateSettlementWithFtsTransferId($this->source, $ftsTransferId);

                break;

            case Constants::REFUND:
                (new RefundCore)->updateRefundWithFtsTransferId($this->source, $ftsTransferId);

                break;

            case Constants::PAYOUT:
                (new PayoutCore)->updatePayoutWithFtsTransferId($this->source, $ftsTransferId);

                break;

            case Constants::FUND_ACCOUNT_VALIDATION:
                (new FundAccountValidationCore)->updateFundAccountValidationWithFtsTransferId(
                    $this->source,
                    $ftsTransferId);

                break;

            default:
                throw new LogicException('Source Type is not supported : ' . $this->fta->getSourceType());

        }
    }
}