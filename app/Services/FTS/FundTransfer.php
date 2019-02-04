<?php

namespace RZP\Services\FTS;

use RZP\Exception\LogicException;
use RZP\Models\Vpa\Core as VPACore;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Payment\Core as PaymentCore;
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

    public function requestFundTransfer(string $ftaId, string $type):array
    {
        $input = $this->makeRequestUsingType($ftaId, $type);

        $response = $this->createAndSendRequest(parent::FundTransferBaseURL . '/' . parent::URLS['request'], 'POST', $input);

        $this->handleResponse($response['body'], $type);

        return $response;
    }

    public function makeRequestUsingType(string $ftaId, string $type):array
    {
        $this->fta = $this->FTACore->getFTAEntityById($ftaId);

        parent::validateChannel($this->fta->getChannel());

        $sourceId   = $this->fta->getSourceId();

        $sourceType = $this->fta->getSourceType();

        $request = [
            //TODO:: Derive product name using source_type and purpose (e.g in case of payout-refund)
            Constants::PRODUCT           => $sourceType,
            Constants::MERCHANT_ID       => $this->fta->merchant->getId(),
        ];

        $request = $this->addTransferBlock($request, $sourceId, $sourceType);

        switch ($type)
        {
            case Constants::FTS_FUND_ACCOUNT:
                $request = $this->addFTSAccountId($request);

                break;

            case Constants::BANK_ACCOUNT:
                $request = $this->addBankAccountDetails($request);

                break;

            case Constants::VPA:
                $request = $this->addVpaDetails($request);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $type);

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
            Constants::ENTITY_ID         => $this->fta->getId(),
            Constants::NARRATION         => $this->fta->getNarration(),
            Constants::INITIATE_AT       => $this->fta->getInitiateAt(),
        ];

        return $request;
    }

    public function addFTSAccountId(array $request):array
    {
        $request[Constants::ACCOUNT] = array(
            Constants::FUND_ACCOUNT_ID   => $this->fta->bankAccount->getFTSAccountId(),
        );

        return $request;
    }

    public function addBankAccountDetails(array $request):array
    {
        $request[Constants::ACCOUNT] = [

                Constants::bankAccount => [
                        Constants::TYPE                       => $this->fta->bankAccount->getType(),
                        Constants::BENEFICIARY_PIN            => $this->fta->bankAccount->getBeneficiaryPin(),
                        Constants::BENEFICIARY_NAME           => $this->fta->bankAccount->getBeneficiaryName(),
                        Constants::BENEFICIARY_CODE           => $this->fta->bankAccount->getBeneficiaryCode(),
                        Constants::BENEFICIARY_CITY           => $this->fta->bankAccount->getBeneficiaryCity(),
                        Constants::BENEFICIARY_STATE          => $this->fta->bankAccount->getBeneficiaryState(),
                        Constants::BENEFICIARY_MOBILE         => $this->fta->bankAccount->getBeneficiaryMobile(),
                        Constants::BENEFICIARY_ADDRESS        => $this->fta->bankAccount->getBeneficiaryAddress1(),
                        Constants::BENEFICIARY_COUNTRY        => $this->fta->bankAccount->getBeneficiaryCountry(),
                        Constants::BENEFICIARY_EMAIL_ID       => $this->fta->bankAccount->getBeneficiaryEMail(),
                        Constants::BENEFICIARY_IFSC_CODE      => $this->fta->bankAccount->getIfscCode(),
                        Constants::BENEFICIARY_BANK_NAME      => $this->fta->bankAccount->getBankName(),
                        Constants::BENEFICIARY_ACCOUNT_TYPE   => $this->fta->bankAccount->getAccountType(),
                        Constants::BENEFICIARY_ACCOUNT_NUMBER => $this->fta->bankAccount->getAccountNumber(),
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
                throw new LogicException('Source Type is not supported ' . $sourceType);

        }
    }

    protected function handleResponse(array $responseBody, string $type)
    {
        $this->updateFTA($responseBody);

        $this->updatePaymentInstrumentByType($responseBody, $type);
    }

    protected function updateFTA(array $responseBody)
    {
        $this->fta->setFTSTransferId($responseBody['transfer_id']);

        $this->fta->setStatus($responseBody['status']);

        $this->FTACore->updateFTA($this->fta);
    }

    protected function updatePaymentInstrument(array $responseBody, string $type)
    {
        switch ($type)
        {
            case Constants::bankAccount:
                $this->fta->bankAccount->setFTSAccountId($responseBody['fa_id']);

                (new BankAccountCore)->updateBankAccountEntity($this->fta->bankAccount);

                break;

            case Constants::VPA:
                $this->fta->vpa->setFTSAccountId($responseBody['fa_id']);

                (new VPACore)->updateVPAEntity($this->fa->vpa);

                break;

            default:
                throw new LogicException('Account Type is not supported ' . $type);
        }
    }
}