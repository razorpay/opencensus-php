<?php

namespace RZP\Services\FTS;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\FundTransfer\Mode;
use RZP\Exception\LogicException;
use RZP\Models\Bank\IFSC as IFSC;
use RZP\Models\Settlement\Channel;
use RZP\Models\Vpa\Core as VPACore;
use RZP\Models\Card\Entity as CardVault;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankAccount\Core as BankAccountCore;
use RZP\Exception\BadRequestValidationFailureException;
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

    protected $bankingStartTimeRtgs;

    protected $bankingEndTimeRtgs;

    const SOURCE_TYPES = [
        Constants::REFUND,
        Constants::PAYOUT,
        Constants::SETTLEMENT,
        Constants::FUND_ACCOUNT_VALIDATION,
    ];

    const CHANNEL_WISE_IFSC_IDENTIFIER = [
        Channel::RBL     => IFSC::RATN,
        Channel::CITI    => IFSC::CITI,
        Channel::ICICI   =>IFSC::ICIC,
        Channel::YESBANK => IFSC::YESB,
    ];

    public function __construct($app)
    {
        parent::__construct($app);

        $this->FTACore = new FundTransferAttempt\Core;
    }

    /**
     * @param string $ftaId
     * @return array
     * @throws LogicException
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function requestFundTransfer(string $ftaId): array
    {
        $this->bankingStartTimeRtgs = Carbon::createFromTime(Constants::RTGS_CUTOFF_HOUR_MIN, 0, 0, Timezone::IST)
                                            ->getTimestamp();

        $this->bankingEndTimeRtgs = Carbon::createFromTime(Constants::RTGS_REVISED_CUTOFF_HOUR_MAX,
                                                           Constants::RTGS_REVISED_CUTOFF_MINUTE_MAX,
                                                           0,
                                                           Timezone::IST)
                                                           ->getTimestamp();

        $input = $this->makeRequestUsingType($ftaId);

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
     * @return array
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    public function makeRequestUsingType(string $ftaId): array
    {
        $this->fta = $this->FTACore->getFTAEntity($ftaId);

        $sourceType = $this->fta->getSourceType();

        $purpose    = $this->fta->getPurpose();

        $this->setSourceEntityByType($sourceType);

        $product = $sourceType;

        $this->accountType = $this->getAccountType();

        if ($sourceType === Constants::FUND_ACCOUNT_VALIDATION)
        {
            $product = Constants::PENNY_TESTING;
        }

        if (($sourceType === Constants::PAYOUT) and
            ($this->fta->isRefund() === true))
        {
            $product = Constants::PAYOUT_REFUND;
        }

        $request = [
            Constants::PRODUCT           => $product,
            Constants::MERCHANT_ID       => $this->fta->merchant->getId(),
        ];

        $request = $this->addTransferBlock($request);

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

        return $request;
    }

    /**
     * @param array $request
     * @return array
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    protected function addTransferBlock(array $request): array
    {
        $mode = $this->getFTSFundTransferMode();

        $channel = $this->fta->getChannel();

        $sourceType = $this->fta->getSourceType();

        $request[Constants::TRANSFER] = [
            Constants::PREFERRED_MODE    => $mode,
            Constants::AMOUNT            => $this->source->getAmount(),
            Constants::NARRATION         => $this->fta->getNarration(),
            Constants::SOURCE_ID         => $this->fta->getSourceId(),
            Constants::SOURCE_TYPE       => $sourceType,
            Constants::INITIATE_AT       => $this->fta->getInitiateAt(),
            Constants::PREFERRED_CHANNEL => $channel,
        ];

        if (($channel === Channel::RBL) and ($sourceType === Entity::PAYOUT))
        {
            $source = $this->fta->source;

            if (method_exists($source, 'getSourceFtsFundAccountId'))
            {
                $request[Constants::TRANSFER] += [
                    Constants::PREFERRED_SOURCE_ACCOUNT_ID => (int) $this->fta->source->getSourceFtsFundAccountId(),
                ];
            }
        }

        $transferBy  = $this->fta->getCreatedAt();

        if ($sourceType === Entity::PAYOUT)
        {
            $transferBy  += $this->getTransferSLA($mode);
        }

        $request[Constants::TRANSFER] += [
            Constants::TRANSFER_BY => $transferBy,
        ];

        return $request;
    }

    /**
     * @param array $request
     * @param int $ftsAccountId
     * @return array
     */
    protected function addFTSFundAccountId(array $request, int $ftsAccountId):array
    {
        $request[Constants::ACCOUNT] = array(
            Constants::FUND_ACCOUNT_ID   => $ftsAccountId,
        );

        return $request;
    }

    /**
     * @param array $request
     * @return array
     */
    protected function addBankAccountDetails(array $request):array
    {
        $ftsAccountId = $this->fta->bankAccount->getFtsFundAccountId();

        if (empty($ftsAccountId) === false)
        {
            return $this->addFTSFundAccountId($request, $ftsAccountId);
        }

        $accountType = $this->fta->bankAccount->getAccountType();

        if (empty($accountType) === true)
        {
            $accountType = Constants::SAVING;
        }

        $request[Constants::ACCOUNT] = [
                Constants::BANK_ACCOUNT => [
                        Constants::IFSC_CODE                  => $this->fta->bankAccount->getIfscCode(),
                        Constants::ACCOUNT_TYPE               => $accountType,
                        Constants::ACCOUNT_NUMBER             => $this->fta->bankAccount->getAccountNumber(),
                        Constants::BENEFICIARY_NAME           => $this->fta->bankAccount->getBeneficiaryName(),
                        Constants::BENEFICIARY_CITY           => $this->fta->bankAccount->getBeneficiaryCity() ?? 'Bangalore',
                        Constants::BENEFICIARY_EMAIL          => $this->fta->bankAccount->getBeneficiaryEmail() ?? 'no-reply@razorpay.com',
                        Constants::BENEFICIARY_STATE          => $this->fta->bankAccount->getBeneficiaryState() ?? 'KA',
                        Constants::BENEFICIARY_MOBILE         => $this->fta->bankAccount->getBeneficiaryMobile() ?? '9999999999',
                        Constants::IS_VIRTUAL_ACCOUNT         => $this->fta->bankAccount->isVirtual(),
                        Constants::BENEFICIARY_ADDRESS        => $this->fta->bankAccount->getBeneficiaryAddress1() ?? 'Razorpay',
                        Constants::BENEFICIARY_COUNTRY        => $this->fta->bankAccount->getBeneficiaryCountry() ?? 'IN',
                ],
        ];

        return $request;
    }

    /**
     * @param array $request
     * @return array
     */
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

    /**
     * @param array $request
     * @return array
     * @throws \Exception
     */
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

    /**
     * @param array $responseBody
     * @param string $type
     */
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
     * @return mixed|string
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    protected function getFTSFundTransferMode()
    {
        if ($this->fta->hasMode() === true)
        {
            return $this->fta->getMode();
        }

        if ($this->accountType === Constants::VPA)
        {
            return Mode::UPI;
        }

        if ($this->accountType === Constants::CARD)
        {
            return $this->getPaymentModeForCard();
        }

        if ($this->accountType === Constants::CARD)
        {
            return $this->getPaymentModeForBankAccount();
        }

        throw new LogicException('Invalid account type '. $this->accountType);
    }

    /**
     * @return string
     * @throws BadRequestValidationFailureException
     */
    protected function getPaymentModeForCard()
    {
        $iin = $this->fta->card->iinRelation;

        if (empty($iin) === true)
        {
            throw new BadRequestValidationFailureException("iin is not valid mode for issuer");
        }

        $issuer         = $iin->getIssuer();

        $amount         = $this->source->getAmount();

        $networkCode    = $iin->getNetworkCode();

        $supportedModes = Mode::getSupportedModes($issuer, $networkCode);

        if ($amount <= Constants::IMPS_CUTOFF_AMOUNT)
        {
            $mode =  Mode::IMPS;
        }
        else
        {
            $mode = Mode::NEFT;

            $now = Carbon::now(Timezone::IST)->getTimestamp();

            if ((($now >= $this->bankingStartTimeRtgs) and
                    ($now <= $this->bankingEndTimeRtgs)) and
                ($amount >= Constants::IMPS_CUTOFF_AMOUNT))
            {
                $mode = Mode::RTGS;
            }
        }

        if (in_array($mode, $supportedModes, true) === true)
        {
            return $mode;
        }
        else if (in_array(Mode::NEFT, $supportedModes, true) === true)
        {
            return Mode::NEFT;
        }
        else
        {
            throw new BadRequestValidationFailureException("$mode is not a valid mode for issuer $issuer");
        }
    }

    /**
     * @return string
     */
    protected function getPaymentModeForBankAccount()
    {
        $channel = $this->fta->getChannel();

        $amount  = $this->source->getAmount();

        if ($channel === Channel::ICICI)
        {
            return Mode::IMPS;
        }

        $ba = $this->fta->bankAccount;

        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        $ifscIdentifier = self::CHANNEL_WISE_IFSC_IDENTIFIER[$channel];

        if (starts_with($ifscFirstFour, $ifscIdentifier) === true)
        {
            return Mode::IFT;
        }

        if ($amount <= Constants::IMPS_CUTOFF_AMOUNT)
        {
            return Mode::IMPS;
        }

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        if ((($now >= $this->bankingStartTimeRtgs) and ($now <= $this->bankingEndTimeRtgs)) and
            ($amount >= Constants::IMPS_CUTOFF_AMOUNT))
        {
            return Mode::RTGS;
        }

        return Mode::NEFT;
    }

    /**
     * @param string $mode
     * @return int
     */
    protected function getTransferSLA(string $mode)
    {
        $sla = (int) $this->redis->HGET(ConfigKey::FTS_TRANSFER_SLA, strtolower($mode));

        if ($sla < 0)
        {
            return 0;
        }

        return $sla;
    }
}
