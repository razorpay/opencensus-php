<?php

namespace RZP\Services\FTS;

use RZP\Constants\Entity;
use RZP\Exception\LogicException;
use RZP\Models\Vpa\Core as VPACore;
use RZP\Models\BankAccount\Core as BankAccountCore;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class FundTransfer extends Base
{
    protected $FTACore;

    /**
     * @var FundTransferAttempt\Entity
     */
    protected $fta;

    protected $source;

    const SOURCE_TYPES = [
        Constants::REFUND,
        Constants::PAYOUT,
        Constants::SETTLEMENT,
        Constants::FUND_ACCOUNT_VALIDATION,
    ];

    public function __construct($app)
    {
        parent::__construct($app);

        $this->FTACore = new FundTransferAttempt\Core;
    }

    /**
     * @param string $ftaId
     * @param string $accountType
     * @param bool   $isRegistered
     * @return array
     * @throws LogicException
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function requestFundTransfer(string $ftaId, string $accountType, bool $isRegistered): array
    {
        $input = $this->makeRequestUsingType($ftaId, $accountType, $isRegistered);

        $response = $this->createAndSendRequest(
            parent::FUND_TRANSFER_CREATE_URI,
            'POST', $input);

        $this->handleResponse($response['body'], $accountType);

        return $response;
    }

    /**
     * @param string $ftaId
     * @param string $type
     * @param bool   $isRegistered
     * @return array
     * @throws LogicException
     */
    public function makeRequestUsingType(string $ftaId, string $type, bool $isRegistered): array
    {
        $this->fta = $this->FTACore->getFTAEntity($ftaId);

        $sourceType = $this->fta->getSourceType();

        $this->setSourceEntityByType($sourceType);

        $product = $sourceType;

        if (($sourceType === Constants::PAYOUT) and
            ($this->fta->isRefund() === true))
        {
            $product .= '_refund';
        }

        $request = [
            Constants::PRODUCT           => $product,
            Constants::MERCHANT_ID       => $this->fta->merchant->getId(),
        ];

        $request = $this->addTransferBlock($request);

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

    /**
     * @param array $request
     * @return array
     */
    protected function addTransferBlock(array $request): array
    {

        $request[Constants::TRANSFER] = [
            Constants::MODE              => $this->fta->getMode(),
            Constants::AMOUNT            => $this->source->getAmount(),
            Constants::CHANNEL           => $this->fta->getChannel(),
            Constants::NARRATION         => $this->fta->getNarration(),
            Constants::SOURCE_ID         => $this->fta->getSourceId(),
            Constants::SOURCE_TYPE       => $this->fta->getSourceType(),
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

    /**
     * @param string $sourceType
     * @throws LogicException
     */
    protected function setSourceEntityByType(string $sourceType)
    {
        if(in_array($sourceType, self::SOURCE_TYPES, true) === false)
        {
            throw new LogicException('Source Type is not supported : ' . $sourceType);
        }

        $this->source = $this->fta->source;
    }

    /**
     * @param array  $responseBody
     * @param string $type
     * @throws LogicException
     */
    protected function handleResponse(array $responseBody, string $type)
    {
        $this->updateFTA($responseBody);

        $this->updatePaymentInstrumentByType($responseBody, $type);
    }

    /**
     * @param array $responseBody
     * @throws LogicException
     */
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

    /**
     * @param $ftsTransferId
     */
    protected function updateSource($ftsTransferId)
    {
        $sourceCoreClass = Entity::getEntityNamespace($this->source->getEntity()) . '\\Core';

        $sourceCore = new $sourceCoreClass();

        $sourceCore->updateEntityWithFtsTransferId($this->source, $ftsTransferId);
    }
}
