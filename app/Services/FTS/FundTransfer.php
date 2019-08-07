<?php

namespace RZP\Services\FTS;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Mode;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\Vpa\Core as VPACore;
use RZP\Models\Card\Entity as CardVault;
use RZP\Models\Settlement\SlackNotification;
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

    protected $accountType;

    const SOURCE_TYPES = [
        Constants::REFUND,
        Constants::PAYOUT,
        Constants::SETTLEMENT,
        Constants::FUND_ACCOUNT_VALIDATION,
    ];

    const PAYOUT_REFUND = 'payout_refund';

    const PENNY_TESTING = 'penny_testing';

    public function __construct($app)
    {
        parent::__construct($app);

        $this->FTACore = new FundTransferAttempt\Core;
    }

    /**
     * @param string $ftaId
     * @param bool   $isRegistered
     * @return array
     * @throws LogicException
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function requestFundTransfer(string $ftaId, bool $isRegistered): array
    {
        $input = $this->makeRequestUsingType($ftaId, $isRegistered);

        $response = $this->createAndSendRequest(
            parent::FUND_TRANSFER_CREATE_URI,
            'POST', $input);

        $this->handleResponse($response['body'], $this->accountType);

        return $response;
    }

    /**
     * @return string
     * @throws LogicException
     */
    public function getAccountType(): string
    {
        if ($this->fta->hasBankAccount())
        {
            return Constants::BANK_ACCOUNT;
        }
        else if ($this->fta->hasVpa())
        {
            return Constants::VPA;
        }
        else if ($this->fta->hasCard())
        {
            return Constants::CARD;
        }
        else
        {
            throw new LogicException('Account Type is not supported ');
        }
    }

    /**
     * @param string $ftaId
     * @param bool   $isRegistered
     * @return array
     * @throws LogicException
     */
    public function makeRequestUsingType(string $ftaId, bool $isRegistered): array
    {
        $this->fta = $this->FTACore->getFTAEntity($ftaId);

        $sourceType = $this->fta->getSourceType();

        $this->setSourceEntityByType($sourceType);

        $product = $sourceType;

        $this->accountType = $this->getAccountType();

        if (($sourceType === Constants::PAYOUT) and
            ($this->fta->isRefund() === true))
        {
            $product = self::PAYOUT_REFUND;
        }
        else if ($sourceType === Constants::FUND_ACCOUNT_VALIDATION)
        {
            $product = self::PENNY_TESTING;
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
            switch ($this->accountType)
            {
                case Constants::BANK_ACCOUNT:
                    $request = $this->addBankAccountDetails($request);

                    break;

                case Constants::VPA:
                    $request = $this->addVpaDetails($request);

                    break;

                case Constants::CARD:
                    $request = $this->addCardDetails($request);

                    break;

                default:
                    throw new LogicException('Account Type is not supported ' . $this->accountType);
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
        $mode = $this->getFTSFundTransferMode();

        $channel = $this->fta->getChannel();

        $request[Constants::TRANSFER] = [
            Constants::PREFERRED_MODE    => $mode,
            Constants::AMOUNT            => $this->source->getAmount(),
            Constants::NARRATION         => $this->fta->getNarration(),
            Constants::SOURCE_ID         => $this->fta->getSourceId(),
            Constants::SOURCE_TYPE       => $this->fta->getSourceType(),
            Constants::INITIATE_AT       => $this->fta->getInitiateAt(),
            Constants::PREFERRED_CHANNEL => $channel,
        ];

        if (in_array($channel, Channel::getFtsSupportedPayoutChannels(), true) === true)
        {
            $source = $this->fta->source;

            if (method_exists($source, 'getSourceFtsFundAccountId'))
            {
                $request[Constants::TRANSFER] = [
                    Constants::PREFERRED_SOURCE_ACCOUNT_ID => $this->fta->source->getSourceFtsFundAccountId(),
                ];
            }
        }

        return $request;
    }

    protected function addFTSFundAccountId(array $request):array
    {
        $request[Constants::ACCOUNT] = array(
            Constants::FUND_ACCOUNT_ID   => $this->fta->bankAccount->getFtsFundAccountId(),
        );

        return $request;
    }

    protected function addBankAccountDetails(array $request):array
    {
        $accountType = $this->fta->bankAccount->getAccountType();

        if(empty($accountType) === true)
        {
            $accountType = Constants::SAVING;
        }

        $request[Constants::ACCOUNT] = [
                Constants::BANK_ACCOUNT => [
                        Constants::IFSC_CODE                  => $this->fta->bankAccount->getIfscCode(),
                        Constants::ACCOUNT_TYPE               => $accountType,
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

    protected function addVpaDetails(array $request):array
    {
        $request[Constants::ACCOUNT] = [
                Constants::VPA => [
                        Constants::HANDLE       => $this->fta->vpa->getHandle(),
                        Constants::USERNAME     => $this->fta->vpa->getUsername(),
                ],
        ];

        return $request;
    }

    protected function addCardDetails(array $request):array
    {
        $request[Constants::ACCOUNT] = [
                Constants::CARD => [
                        Constants::ISSUER_BANK => $this->fta->card->getIssuer(),
                        Constants::VAULT_TOKEN => $this->getCardVaultToken($this->fta->card),
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
     */
    protected function handleResponse(array $responseBody, string $type)
    {
        $this->updateFTA($responseBody);

        $this->updatePaymentInstrumentByType($responseBody, $type);
    }

    /**
     * @param array $responseBody
     */
    protected function updateFTA(array $responseBody)
    {
        $ftsTransferId = $responseBody[Constants::FUND_TRANSFER_ID];

        $responseBody[Constants::STATUS] = strtolower($responseBody[Constants::STATUS]);

        if(strcasecmp($responseBody[Constants::STATUS], Constants::STATUS_CREATED) === 0)
        {
            $responseBody[Constants::STATUS] = Constants::STATUS_INITIATED;
        }

        $this->FTACore->updateFTA($this->fta, $ftsTransferId, $responseBody[Constants::STATUS]);

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

    /**
     * If card is used for the 1st time on a RZP gateway then a vault token is generated in card entity.
     * If vault has been already encountered then vault token is null and a global card id is present.
     * This contains the vault token generated.
     * If no vault token is present then null is returned to mark fta as failed.
     *
     * @param CardVault $card
     * @return mixed
     * @throws \Exception
     */
    protected function getCardVaultToken(CardVault $card)
    {
        $token = $card->getCardVaultToken();

        if ($token === null)
        {
            $this->trace->error(
                TraceCode::CARD_TOKEN_IS_NOT_AVAILABLE,
                [
                    'card_id' => $card->getId()
                ]);

            (new SlackNotification())->send(
                'Vault token missing',
                [
                    'card_id' => $card->getId()
                ],
                null, 1, 'fts_alerts');
        }

        return $token;
    }

    /**
     * This is a unified method for
     * determining fund transfer mode for
     * all FTS supported channels
     *
     * @return mixed|string
     */
    protected function getFTSFundTransferMode()
    {
        $channel = $this->fta->getChannel();

        $amount  = $this->source->getAmount();

        if ($this->fta->hasMode() === true)
        {
            return $this->fta->getMode();
        }

        if ($channel === Channel::ICICI)
        {
            return Mode::IMPS;
        }

        $ba = $this->fta->bankAccount;

        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        $channelClass = '\RZP\\Models\\FundTransfer\\' . studly_case($channel) . '\\NodalAccount';

        if (starts_with($ifscFirstFour, $channelClass::IFSC_IDENTIFIER) === true)
        {
            return Mode::IFT;
        }

        if ($amount < $channelClass::MAX_IMPS_AMOUNT)
        {
            return Mode::IMPS;
        }

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $bankingStartTimeRtgs = Carbon::createFromTime($channelClass::RTGS_CUTOFF_HOUR_MIN, 0, 0, Timezone::IST)
                                      ->getTimestamp();

        $bankingEndTimeRtgs = Carbon::createFromTime(
                                        $channelClass::RTGS_REVISED_CUTOFF_HOUR_MAX,
                                        $channelClass::RTGS_REVISED_CUTOFF_MINUTE_MAX,
                                        0,
                                        Timezone::IST)
                                        ->getTimestamp();

        if ((($now >= $bankingStartTimeRtgs) and ($now <= $bankingEndTimeRtgs)) and
            ($amount >= $channelClass::MIN_RTGS_AMOUNT))
        {
            return Mode::RTGS;
        }

        return Mode::NEFT;
    }
}