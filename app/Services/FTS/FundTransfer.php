<?php

namespace RZP\Services\FTS;

use RZP\Exception\LogicException;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Payment\Core as PaymentCore;
use RZP\Models\Settlement\Core as SettlementCore;
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

        //TODO:: Add Logic to update Source and FTA using response
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
                throw new LogicException('Account Type is not supported ' . $this->type);

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
            Constants::FUND_ACCOUNT_ID   => $this->fta->bank_account->getFTSAccountId(),
        );

        return $request;
    }

    public function addBankAccountDetails(array $request):array
    {
        $request[Constants::ACCOUNT] = [

                Constants::BANK_ACCOUNT => [
                        Constants::TYPE                       => $this->fta->bank_account->getType(),
                        Constants::BENEFICIARY_PIN            => $this->fta->bank_account->getBeneficiaryPin(),
                        Constants::BENEFICIARY_NAME           => $this->fta->bank_account->getBeneficiaryName(),
                        Constants::BENEFICIARY_CODE           => $this->fta->bank_account->getBeneficiaryCode(),
                        Constants::BENEFICIARY_CITY           => $this->fta->bank_account->getBeneficiaryCity(),
                        Constants::BENEFICIARY_STATE          => $this->fta->bank_account->getBeneficiaryState(),
                        Constants::BENEFICIARY_MOBILE         => $this->fta->bank_account->getBeneficiaryMobile(),
                        Constants::BENEFICIARY_ADDRESS        => $this->fta->bank_account->getBeneficiaryAddress1(),
                        Constants::BENEFICIARY_COUNTRY        => $this->fta->bank_account->getBeneficiaryCountry(),
                        Constants::BENEFICIARY_EMAIL_ID       => $this->fta->bank_account->getBeneficiaryEMail(),
                        Constants::BENEFICIARY_IFSC_CODE      => $this->fta->bank_account->getIfscCode(),
                        Constants::BENEFICIARY_BANK_NAME      => $this->fta->bank_account->getBankName(),
                        Constants::BENEFICIARY_ACCOUNT_TYPE   => $this->fta->bank_account->getAccountType(),
                        Constants::BENEFICIARY_ACCOUNT_NUMBER => $this->fta->bank_account->getAccountNumber(),
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
                throw new LogicException('Source Type is not supported ' . $this->type);

        }
    }
}