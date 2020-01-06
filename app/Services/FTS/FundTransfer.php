<?php

namespace RZP\Services\FTS;

use Requests;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\FundTransfer\Mode;
use RZP\Exception\LogicException;
use RZP\Models\Bank\IFSC as IFSC;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Vpa\Core as VPACore;
use RZP\Models\Card\Entity as CardVault;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankAccount\Core as BankAccountCore;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Holidays as TransferHoliday;
use RZP\Models\Settlement\Holidays as SettlementHoliday;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class FundTransfer extends Base
{
    protected $FTACore;

    /**
     * @var FundTransferAttempt\Entity
     */
    protected $fta;

    protected $source;

    protected $amount;

    protected $accountType;

    protected $bankingStartTime;

    protected $bankingEndTimeRtgs;

    protected $bankingEndTimeNeft;

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
     * @return array
     * @throws LogicException
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function requestFundTransfer(): array
    {
        $input = $this->makeRequestUsingType();

        $this->updateFTAWithResponse();

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
     * @return array
     * @throws LogicException
     * @throws \Exception
     */
    public function makeRequestUsingType(): array
    {
        $sourceType = $this->fta->getSourceType();

        $purpose    = $this->fta->getPurpose();

        $product = $sourceType;

        if ($sourceType === Constants::FUND_ACCOUNT_VALIDATION)
        {
            $product = Constants::PENNY_TESTING;
        }

        if ($sourceType === Constants::PAYOUT)
        {
            // Note: Yesbank NEFT/RTGS and UPI integration both uses same source account in FTS.
            // Now, if Mode is UPI then beneficiary registration is not required.
            // As a result, transfers via mode UPI will not have a entry in beneficiary status entity.
            // So, adding a temporary fix for this now to enable yesbank UPI.
            // TODO: Need to have a better way of handling such situations.
            // Thread: https://razorpay.slack.com/archives/CNXASR0H3/p1576752834010000
            // JIRA: https://razorpay.atlassian.net/browse/RX-1112
            if (($this->fta->getChannel() === Channel::YESBANK) and ($this->fta->getMode() === Mode::UPI))
            {
                $product = Constants::PAYOUT_REFUND;
            }

            if ($this->fta->isRefund() === true)
            {
                $product = Constants::PAYOUT_REFUND;
            }
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
     */
    protected function addTransferBlock(array $request): array
    {
        $channel = $this->fta->getChannel();

        $sourceType = $this->fta->getSourceType();

        $request[Constants::TRANSFER] = [
            Constants::PREFERRED_MODE    => $this->fta->getMode(),
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
            $transferBy  += $this->getTransferSLA($this->fta->getMode());
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
//        $ftsAccountId = $this->fta->bankAccount->getFtsFundAccountId();
//
//        if (empty($ftsAccountId) === false)
//        {
//            return $this->addFTSFundAccountId($request, $ftsAccountId);
//        }

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
                        Constants::BENEFICIARY_CITY           => $this->fta->bankAccount->getBeneficiaryCity(),
                        Constants::BENEFICIARY_EMAIL          => $this->fta->bankAccount->getBeneficiaryEmail(),
                        Constants::BENEFICIARY_STATE          => $this->fta->bankAccount->getBeneficiaryState(),
                        Constants::BENEFICIARY_MOBILE         => $this->fta->bankAccount->getBeneficiaryMobile(),
                        Constants::IS_VIRTUAL_ACCOUNT         => $this->fta->bankAccount->isVirtual(),
                        Constants::BENEFICIARY_ADDRESS        => $this->fta->bankAccount->getBeneficiaryAddress1(),
                        Constants::BENEFICIARY_COUNTRY        => $this->fta->bankAccount->getBeneficiaryCountry(),
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
                        Constants::ISSUER_BANK  => $this->fta->card->getIssuer(),
                        Constants::VAULT_TOKEN  => $this->getCardVaultToken($this->fta->card),
                        Constants::NAME         => $this->fta->card->getName(),
                        Constants::NETWORK_CODE => $this->fta->card->getNetworkCode(),
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
        $this->updateFTAWithResponse($responseBody);

//        $this->updatePaymentInstrumentByType($responseBody, $type);
    }

    /**
     * @param array $responseBody
     */
    protected function updateFTAWithResponse(array $responseBody = [])
    {
        $failureReason = null;

        $ftsTransferId = 0;

        $status        = Constants::STATUS_INITIATED;

        if (isset($responseBody[Constants::FUND_TRANSFER_ID]) === true)
        {
            $ftsTransferId = $responseBody[Constants::FUND_TRANSFER_ID];
        }

        if ((isset($responseBody[Constants::INTERNAL_ERROR]) === true) and
            ((isset($responseBody[Constants::INTERNAL_ERROR][Constants::CODE]) === true) and
                ($responseBody[Constants::INTERNAL_ERROR][Constants::CODE] === Constants::VALIDATION_ERROR)))
        {
            $status = Constants::STATUS_FAILED;

            if (isset($responseBody[Constants::INTERNAL_ERROR][Constants::MESSAGE]) === true)
            {
                $failureReason = $responseBody[Constants::INTERNAL_ERROR][Constants::MESSAGE];
            }
        }

        $this->FTACore->updateFTA($this->fta, $ftsTransferId, $status, $failureReason);

        try
        {
            $this->updateSource($this->fta);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::FTS_FUND_TRANSFER_SOURCE_UPDATE_FAILED,
                [
                    'fta_id'            => $this->fta->getId(),
                    'source'            => $this->source->getId(),
                    'status'            => $status,
                    'failure_reason'    => $failureReason,
                    'fts_transfer_id'   => $ftsTransferId,
                ]);
        }
    }

//    /**
//     * @param array $responseBody
//     * @param string $type
//     */
//    protected function updatePaymentInstrumentByType(array $responseBody, string $type)
//    {
//        switch ($type)
//        {
//            case Constants::BANK_ACCOUNT:
//                (new BankAccountCore)->updateBankAccountWithFtsId(
//                    $this->fta->bankAccount,
//                    $responseBody[Constants::FUND_ACCOUNT_ID]);
//
//                break;
//
//            case Constants::VPA:
//                (new VPACore)->updateVpaWithFtsId($this->fta->vpa, $responseBody[Constants::FUND_ACCOUNT_ID]);
//
//                break;
//        }
//    }

    /**
     * @param FundTransferAttempt\Entity $fta
     */
    protected function updateSource(FundTransferAttempt\Entity $fta)
    {
        $source = $fta->source;

        $sourceCoreClass = Entity::getEntityNamespace($source->getEntity()) . '\\Core';

        $sourceCore = new $sourceCoreClass();

        $sourceCore->updateEntityWithFtsTransferId($source, $fta->getFTSTransferId());

        if (method_exists($sourceCore, 'updateStatusAfterFtaInitiated') === true)
        {
            $sourceCore->updateStatusAfterFtaInitiated($source, $this->fta);
        }

        if ($fta->getStatus() === FundTransferAttempt\Status::FAILED)
        {
            (new FundTransferAttempt\Core)->updateSourceEntityByFta($fta);
        }
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

            (new SlackNotification)->send(
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
            return [$this->fta->getMode(), false];
        }

        if ($this->accountType === Constants::VPA)
        {
            return [Mode::UPI, true];
        }

        if ($this->accountType === Constants::CARD)
        {
            return [$this->getPaymentModeForCard(), true];
        }

        if ($this->accountType === Constants::BANK_ACCOUNT)
        {
            return [$this->getPaymentModeForBankAccount(), true];
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

        $networkCode    = $iin->getNetworkCode();

        $supportedModes = Mode::getSupportedModes($issuer, $networkCode);

        if ($this->amount <= Constants::IMPS_CUTOFF_AMOUNT)
        {
            $mode =  Mode::IMPS;
        }
        else
        {
            $mode = Mode::NEFT;

            $now = Carbon::now(Timezone::IST)->getTimestamp();

            if ((($now >= $this->bankingStartTime) and
                    ($now <= $this->bankingEndTimeRtgs)) and
                ($this->amount >= Constants::IMPS_CUTOFF_AMOUNT))
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

        if ($channel === Channel::ICICI)
        {
            return Mode::IMPS;
        }

        $ba = $this->fta->bankAccount;

        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        $ifscIdentifier = IFSC::YESB;

        if ((starts_with($ifscFirstFour, $ifscIdentifier) === true) and ($channel === Channel::YESBANK))
        {
            $ifscLastDigits = substr($ifsc, 4, strlen($ifsc)-4);

            if (is_numeric($ifscLastDigits) === true)
            {
                return Mode::IFT;
            }
            else
            {
                return Mode::NEFT;
            }
        }

        if ($this->amount <= Constants::IMPS_CUTOFF_AMOUNT)
        {
            return Mode::IMPS;
        }

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        if ((($now >= $this->bankingStartTime) and ($now <= $this->bankingEndTimeRtgs)) and
            ($this->amount >= Constants::IMPS_CUTOFF_AMOUNT))
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

    public function bulkUpdateFtsAttempts(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_ATTEMPTS_UPDATE_URI,
            Requests::PATCH,
            $input);
    }

    public function modifyModeIfRequired()
    {
        // Assumption is that the validation would have happened already before this
        // step and hence we can assume that the bank account exists and is valid.
        $channel = $this->fta->getChannel();

        $ba = $this->fta->bankAccount;

        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        $ifscIdentifier = IFSC::YESB;

        if ((starts_with($ifscFirstFour, $ifscIdentifier) === true) and ($channel === Channel::YESBANK))
        {
            $ifscLastDigits = substr($ifsc, 4, strlen($ifsc)-4);

            if (is_numeric($ifscLastDigits) === true)
            {
                $this->fta->setMode(Mode::IFT);
            }
            else
            {
                $this->fta->setMode(Mode::NEFT);
            }
        }
    }

    public function shouldAllowTransfersViaFts()
    {
        list($mode, $shouldUpdateMode) = $this->getFTSFundTransferMode();

        $this->modifyModeIfRequired();

        if ($shouldUpdateMode === true)
        {
            $this->fta->setMode($mode);
        }

        $allowedModes = Mode::get24x7FtsTransferModes();

        if (in_array($mode, $allowedModes, true) === true)
        {
            return [true, 'Allowed modes check passed'];
        }

        $isHoliday = $this->isHolidayForSource();

        if ($isHoliday === true)
        {
            return [false, 'Holiday for source'];
        }

        return $this->isNeftRtgsSupportedTimings($mode);
    }

    public function addInitiateAtIfRequired()
    {
        if (($this->fta->getSourceType() === FundTransferAttempt\Type::PAYOUT) and
            ($this->fta->source->isBalanceTypeBanking() === true))
        {
            $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

            if (($currentTime < $this->bankingStartTime) &&
                (TransferHoliday::isWorkingDay(Carbon::now(Timezone::IST)) === true))
            {
                $this->fta->setInitiateAt($this->bankingStartTime);
            }
            else
            {
                $this->fta->setInitiateAt(TransferHoliday::getNextWorkingDay(Carbon::now(Timezone::IST))
                          ->addHours(Constants::RTGS_CUTOFF_HOUR_MIN)->addMinutes(15)->getTimestamp());
            }

            return true;
        }

        return false;
    }

    public function initialize(string $ftaId)
    {
        $this->fta = $this->FTACore->getFTAEntity($ftaId);

        $this->bankingStartTime = Carbon::createFromTime(Constants::RTGS_CUTOFF_HOUR_MIN, 15, 0, Timezone::IST)
                                        ->getTimestamp();

        $this->bankingEndTimeRtgs = Carbon::createFromTime(Constants::RTGS_REVISED_CUTOFF_HOUR_MAX,
                                                           Constants::RTGS_REVISED_CUTOFF_MINUTE_MAX,
                                                           0,
                                                           Timezone::IST)
                                           ->getTimestamp();

        $this->bankingEndTimeNeft = Carbon::today(Timezone::IST)->hour(18)->minute(15)->getTimestamp();

        $this->accountType = $this->getAccountType();

        $sourceType = $this->fta->getSourceType();

        $this->setSourceEntityByType($sourceType);

        $this->amount = $this->source->getAmount()/100;

        $this->amount = round($this->amount, 2);
    }

    protected function isNeftRtgsSupportedTimings($mode)
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        if ($mode === Mode::RTGS)
        {
            if (($currentTime >= $this->bankingStartTime) and
                ($currentTime <= $this->bankingEndTimeRtgs))
            {
                return [true, 'Rtgs transfer check passed'];
            }
        }
        else
        {
            if (($currentTime >= $this->bankingStartTime) and
                ($currentTime <= $this->bankingEndTimeNeft))
            {
                return [true, 'NEFT transfer check passed'];
            }
        }

        return [false, 'NEFT/RTGS transfer check failed'];
    }

    protected function isHolidayForSource()
    {
        $sourceType = $this->fta->getSourceType();

        $isHoliday = false;

        $currentDateTime = Carbon::now(Timezone::IST);

        if (SettlementHoliday::isWorkingDay($currentDateTime) === false)
        {
            $isHoliday = true;
        }

        if ((($sourceType === FundTransferAttempt\Type::PAYOUT) and
            ($this->fta->source->isBalanceTypeBanking() === true)) and
            (TransferHoliday::isWorkingDay($currentDateTime) === true))
        {
            $isHoliday = false;
        }

        return $isHoliday;
    }

    public function getBulkTransferStatus(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_ATTEMPTS_FETCH_STATUS,
            Requests::POST,
            $input);
    }

    public function checkTransferStatus(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_ATTEMPTS_CHECK_STATUS,
            Requests::POST,
            $input);
    }

    public function getRawBankStatus(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_ATTEMPTS_RAW_BANK_STATUS,
            Requests::POST,
            $input);
    }
}
