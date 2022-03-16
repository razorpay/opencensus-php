<?php

namespace RZP\Services\FTS;

use App;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Http\Request\Requests;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Mode;
use RZP\Exception\LogicException;
use RZP\Models\Bank\IFSC as IFSC;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Vpa\Core as VPACore;
use RZP\Models\Base\PublicCollection;
use RZP\Constants\Mode as ModeConstants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Card\Entity as CardVault;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\BankAccount\Core as BankAccountCore;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Holidays as TransferHoliday;
use RZP\Models\Settlement\Holidays as SettlementHoliday;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\Payout as Payout;
use Razorpay\IFSC\IFSC as BankIFSC;

class FundTransfer extends Base
{
    protected $FTACore;

    protected $payout;

    protected $app;

    /**
     * @var FundTransferAttempt\Entity
     */
    protected $fta;

    protected $source;

    protected $amount;

    protected $accountType;

    protected $bankingStartTimeRtgs;

    protected $bankingEndTimeRtgs;

    protected $bankingStartTimeNeft;

    protected $bankingEndTimeNeft;

    protected $startTimeHourNeft;

    protected $startTimeHourRtgs;

    /**
     * Array to keep working hours of NEFT/RTGS for various channels
     * @var Array
     */
    protected $workingHours = [
        Channel::YESBANK => [
            Mode::RTGS => [
                self::START_TIME => [
                    self::HOURS => Constants::RTGS_CUTOFF_HOUR_MIN,
                    self::MINUTES => 0,
                ],
                self::END_TIME => [
                    self::HOURS => Constants::RTGS_REVISED_CUTOFF_HOUR_MAX,
                    self::MINUTES => Constants::RTGS_REVISED_CUTOFF_MINUTE_MAX,
                ],
            ],
            Mode::NEFT => [
                self::START_TIME => [
                    self::HOURS => 1,
                    self::MINUTES => 0,
                ],
                self::END_TIME => [
                    self::HOURS => 18,
                    self::MINUTES => 45,
                ],
            ],
        ],
        Channel::ICICI => [
            Mode::RTGS => [
                self::START_TIME => [
                    self::HOURS => Constants::RTGS_CUTOFF_HOUR_MIN,
                    self::MINUTES => 0,
                ],
                self::END_TIME => [
                    self::HOURS => Constants::RTGS_REVISED_CUTOFF_HOUR_MAX,
                    self::MINUTES => Constants::RTGS_REVISED_CUTOFF_MINUTE_MAX,
                ],
            ],
            Mode::NEFT => [
                self::START_TIME => [
                    self::HOURS => 1,
                    self::MINUTES => 0,
                ],
                self::END_TIME => [
                    self::HOURS => 18,
                    self::MINUTES => 45,
                ],
            ],
        ],
        self::DEFAULT => [
            Mode::RTGS => [
                self::START_TIME => [
                    self::HOURS => Constants::RTGS_CUTOFF_HOUR_MIN,
                    self::MINUTES => 0,
                ],
                self::END_TIME => [
                    self::HOURS => Constants::RTGS_REVISED_CUTOFF_HOUR_MAX,
                    self::MINUTES => Constants::RTGS_REVISED_CUTOFF_MINUTE_MAX,
                ],
            ],
            Mode::NEFT => [
                self::START_TIME => [
                    self::HOURS => Constants::RTGS_CUTOFF_HOUR_MIN,
                    self::MINUTES => 0,
                ],
                self::END_TIME => [
                    self::HOURS => 18,
                    self::MINUTES => 15,
                ],
            ],
        ],
    ];

    // add channel and identifier here for new channels
    protected $channelToIdentifierMapping = [
        Channel::ICICI   => IFSC::ICIC,
        Channel::YESBANK => IFSC::YESB,
    ];

    protected $iciciVaIfsc = [
        'ICIC0000103',
        'ICIC0000104',
        'ICIC0000106',
    ];

    const SOURCE_TYPES = [
        Constants::REFUND,
        Constants::PAYOUT,
        Constants::SETTLEMENT,
        Constants::FUND_ACCOUNT_VALIDATION,
    ];
    const DEFAULT = 'default';
    const HOURS = 'hours';
    const MINUTES = 'minutes';
    const END_TIME = 'end_time';
    const START_TIME = 'start_time';

    public function __construct($app)
    {
        parent::__construct($app);

        $this->FTACore = new FundTransferAttempt\Core;

        $this->payout  = new Payout\Core;

        $this->app     = $app;
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
        else if ($this->fta->hasWalletAccount())
        {
            return Constants::WALLET;
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
        $source = $this->fta->source;

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

            if (($this->fta->isRefund() === true) && ($source->isBalanceAccountTypeDirect() === false))
            {
                $product = Constants::PAYOUT_REFUND;
            }

            if ($source->getPayoutType() === PayoutEntity::ON_DEMAND)
            {
                $product = Constants::ES_ON_DEMAND;
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

            case Constants::WALLET:
                $request = $this->addWalletAccountDetails($request);

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

        // Ref: https://razorpay.slack.com/archives/CNXASR0H3/p1586861024155400
        if (($sourceType === Entity::PAYOUT) and ($channel === Channel::YESBANK))
        {
            $channel = Channel::ICICI;
        }

        $request[Constants::TRANSFER] = [
            Constants::PREFERRED_MODE    => $this->fta->getMode(),
            Constants::AMOUNT            => $this->source->getAmount(),
            Constants::NARRATION         => $this->fta->getNarration(),
            Constants::SOURCE_ID         => $this->fta->getSourceId(),
            Constants::SOURCE_TYPE       => $sourceType,
            Constants::INITIATE_AT       => $this->fta->getInitiateAt(),
            Constants::PREFERRED_CHANNEL => $channel,
        ];

        //
        // In case of refunds - we need to use base amount
        // since there could be payments of international currencies and in FTA we are always using INR
        //
        if ($sourceType === Entity::REFUND)
        {
            $request[Constants::TRANSFER][Constants::AMOUNT] = $this->source->getBaseAmount();
        }

        $source = $this->fta->source;

        if ($sourceType === Entity::PAYOUT)
        {
            if ((in_array($channel, Channel::getNonTransactionChannels(), true) === true) and
                ($source->balance->getAccountType() === Balance\AccountType::DIRECT))
            {
                if (method_exists($source, 'getSourceFtsFundAccountId') === true)
                {
                    $request[Constants::TRANSFER] += [
                        Constants::PREFERRED_SOURCE_ACCOUNT_ID => (int) $this->fta->source->getSourceFtsFundAccountId(),
                    ];
                }
            }

            /**
             * Putting this behind feature flag for 2 reasons
             * 1. Safety check, as this is only required for ICICI OPGSP international export settlement flow
             * 2. Since preferred channel is not being passed, fts will rely on validating request notes if key is present.
             */
            if ($this->fta->merchant->isFTSRequestNotesEnabled() === true)
            {
                $this->addRequestMetaToTransferBlock($request, $source);
            }
        }

        if (method_exists($source, 'hasBatch'))
        {
            $request[Constants::TRANSFER] += [
                Constants::IS_BATCH => $this->fta->source->hasBatch(),
            ];
        }

        return $request;
    }

    protected function addRequestMetaToTransferBlock(array & $request, Payout\Entity $payout) {

        if($payout === null)
        {
            return;
        }

        $contactNotes = [];

        if(($payout->fundAccount !== null) and
            ($payout->fundAccount->getSourceType() === \RZP\Models\FundAccount\Entity::CONTACT) and
            (empty($payout->fundAccount->getSourceId()) === false))
        {
            /** @var $contact \RZP\Models\Contact\Entity */
            $contact = $payout->fundAccount->contact;

            $contactNotes += $contact->getNotes()->toArray();
        }

        if ($this->fta->bankAccount !== null)
        {
            $contactNotes += [
                Constants::BENEFICIARY_BANK_NAME => BankIFSC::getBankName(substr($this->fta->bankAccount->getIfscCode(), 0, 4))
            ];
        }

        $request[Constants::TRANSFER] += [
            Constants::REQUEST_META => [
                Constants::CONTACT_NOTES => $contactNotes,
                Constants::PAYOUT_NOTES  => $payout->getNotes()
            ]
        ];
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
     */
    protected function addWalletAccountDetails(array $request):array
    {
        $request[Constants::ACCOUNT] = [
                Constants::WALLET => [
                        Constants::BENEFICIARY_MOBILE => $this->fta->walletAccount->getPhone(),
                        Constants::PROVIDER           => $this->fta->walletAccount->getProvider(),
                        Constants::BENEFICIARY_EMAIL  => $this->fta->walletAccount->getEmail(),
                        Constants::BENEFICIARY_NAME   => $this->fta->walletAccount->getName(),
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

        if ($this->fta->getSourceType() === Constants::REFUND)
        {
            // ToDo : add isTokenPan() check when flow supports IR on token pan payments
            // Ref : https://razorpay.slack.com/archives/C01CDL71EEM/p1643688960600149
            if ($this->fta->card->isNetworkTokenisedCard() === true)
            {
                $request[Constants::ACCOUNT][Constants::CARD][Constants::TOKENISED] = true;
            }
        }

        if (($this->fta->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::ALLOW_NON_SAVED_CARDS) === true) and
            ($this->fta->getSourceType() === Constants::PAYOUT))
        {
            $request[Constants::ACCOUNT][Constants::CARD][Constants::TOKENISED] = $this->fta->card->isTokenPan();
        }

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

        if ((isset($responseBody[Constants::STATUS]) === true) and
            (strtolower($responseBody[Constants::STATUS]) !== Constants::STATUS_CREATED))
        {
            $status = strtolower($responseBody[Constants::STATUS]);
        }

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

        if ($fta->getStatus() === FundTransferAttempt\Status::INITIATED)
        {
            if (method_exists($sourceCore, 'updateStatusAfterFtaInitiated') === true)
            {
                $sourceCore->updateStatusAfterFtaInitiated($source, $this->fta);
            }
        }
        else
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
        if ($this->accountType === Constants::WALLET)
        {
            return [Constants::WALLET_TRANSFER_MODE_FTS, true];
        }

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

            if ((($now >= $this->bankingStartTimeRtgs) and
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

        if ((($now >= $this->bankingStartTimeRtgs) and ($now <= $this->bankingEndTimeRtgs)) and
            ($this->amount >= Constants::IMPS_CUTOFF_AMOUNT))
        {
            return Mode::RTGS;
        }

        return Mode::NEFT;
    }

    public function bulkUpdateFtsAttempts(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_ATTEMPTS_UPDATE_URI,
            Requests::PATCH,
            $input);
    }

    public function shouldAllowTransfersViaFts()
    {
        if ($this->mode === ModeConstants::TEST)
        {
            return [false, 'Transfers not allowed on test mode'];
        }

        list($mode, $shouldUpdateMode) = $this->getFTSFundTransferMode();

        if ($shouldUpdateMode === true)
        {
            $this->fta->setMode($mode);
        }

        if ($shouldUpdateMode === true)
        {
            $this->FTACore->updateFTA($this->fta, 0);
        }

        return [true, 'All merchants allowed'];

    }

    public function addInitiateAtIfRequired()
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $minuteOffset = random_int(15, 59);

        $channel = $this->fta->getChannel();

        $mode = $this->fta->getMode();

        $bankingStartTimeByMode = $this->bankingStartTimeNeft;

        $startTimeHour = $this->startTimeHourNeft;

        if ($mode === Mode::RTGS)
        {
            $bankingStartTimeByMode = $this->bankingStartTimeRtgs;

            $startTimeHour = $this->startTimeHourRtgs;
        }

        if ($this->fta->source->isBalanceTypeBanking() === true)
        {
            // We don't want to sent requests for FTS for test mode
            // until FTS has proper setup for test mode which is being maintained
            if ($this->mode === ModeConstants::TEST)
            {
                return false;
            }

            if (($currentTime < $bankingStartTimeByMode) &&
                (TransferHoliday::isWorkingDay(Carbon::now(Timezone::IST)) === true))
            {
                $this->fta->setInitiateAt(Carbon::createFromTime($startTimeHour,
                                                                 $minuteOffset,
                                                                0,
                                                                Timezone::IST)
                          ->getTimestamp());
            }
            else
            {
                $this->fta->setInitiateAt(TransferHoliday::getNextWorkingDay(Carbon::now(Timezone::IST))
                          ->addHours($startTimeHour)->addMinutes($minuteOffset)->getTimestamp());
            }
        }
        else
        {
            if (($currentTime < $bankingStartTimeByMode) &&
                (SettlementHoliday::isWorkingDay(Carbon::now(Timezone::IST)) === true))
            {
                $this->fta->setInitiateAt(Carbon::createFromTime($startTimeHour,
                                                                 $minuteOffset,
                                                               0,
                                                               Timezone::IST)
                          ->getTimestamp());
            }
            else
            {
                $this->fta->setInitiateAt(SettlementHoliday::getNextWorkingDay(Carbon::now(Timezone::IST))
                          ->addHours($startTimeHour)->addMinutes($minuteOffset)->getTimestamp());
            }
        }

        return true;
    }

    public function initialize(string $ftaId)
    {
        $this->fta = $this->FTACore->getFTAEntity($ftaId);

        $channel = $this->fta->getChannel();

        $startTimeNeft = $this->workingHours[self::DEFAULT][Mode::NEFT][self::START_TIME];

        $startTimeRtgs = $this->workingHours[self::DEFAULT][Mode::RTGS][self::START_TIME];

        $endTimeRtgs = $this->workingHours[self::DEFAULT][Mode::RTGS][self::END_TIME];

        $endTimeNeft = $this->workingHours[self::DEFAULT][Mode::NEFT][self::END_TIME];

        $this->startTimeHourNeft = $startTimeNeft[self::HOURS];

        $this->startTimeHourRtgs = $startTimeRtgs[self::HOURS];

        if (isset($this->workingHours[$channel]) === true)
        {
            $startTimeRtgs = $this->workingHours[$channel][Mode::RTGS][self::START_TIME];

            $endTimeRtgs = $this->workingHours[$channel][Mode::RTGS][self::END_TIME];

            $startTimeNeft = $this->workingHours[$channel][Mode::NEFT][self::START_TIME];

            $endTimeNeft = $this->workingHours[$channel][Mode::NEFT][self::END_TIME];

            $this->startTimeHourNeft = $startTimeNeft[self::HOURS];

            $this->startTimeHourRtgs = $startTimeRtgs[self::HOURS];
        }

        $this->bankingStartTimeRtgs = Carbon::createFromTime($startTimeRtgs[self::HOURS],
                                                             $startTimeRtgs[self::MINUTES],
                                                            0,
                                                            Timezone::IST)
                                            ->getTimestamp();

        $this->bankingEndTimeRtgs = Carbon::createFromTime($endTimeRtgs[self::HOURS],
                                                           $endTimeRtgs[self::MINUTES],
                                                          0,
                                                          Timezone::IST)
                                            ->getTimestamp();

        $this->bankingStartTimeNeft = Carbon::createFromTime($startTimeNeft[self::HOURS],
                                                             $startTimeNeft[self::MINUTES],
                                                             0,
                                                             Timezone::IST)
                                            ->getTimestamp();

        $this->bankingEndTimeNeft = Carbon::today(Timezone::IST)->hour($endTimeNeft[self::HOURS])
                                            ->minute($endTimeNeft[self::MINUTES])->getTimestamp();

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
            if (($currentTime >= $this->bankingStartTimeRtgs) and
                ($currentTime <= $this->bankingEndTimeRtgs))
            {
                return [true, 'Rtgs transfer check passed'];
            }
        }
        else
        {
            if (($currentTime >= $this->bankingStartTimeNeft) and
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

    public function getPendingFundTransfers(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_GET_PENDING_TRANSFERS,
            Requests::GET,
            $input
        );
    }

    public function sendAlertIfLowBalance(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::SEND_LOW_BALANCE_ALERT,
            Requests::POST,
            $input
        );
    }

    public function fetchAccountBalance(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FETCH_ACCOUNT_BALANCE,
            Requests::POST,
            $input
        );
    }

    public function getBulkStatus(array $input)
    {
        (new Validator)->validateInput('fetch_transfer_status', $input);

        try
        {
            $payouts = $this->repo->payout->findMany($input['source_ids']);

            $ids = implode(",",$input['source_ids']);

            $request = [
              'source_id'   => $ids,
              'source_type' => 'payout',
            ];

            $response  = $this->app['fts_fund_transfer']->createAndSendRequest(
              parent::FUND_TRANSFER_ATTEMPTS_STATUS_FETCH,
              Requests::GET,
              $request)['body']['transfers'];

            $data = $this->extractFtsResponse($response);

            return $this->combineStatusForApiAndFTS($payouts, $data);

        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::FTS_TRANSFER_STATUS_FETCH_FAILED,
                [
                    'input' => $input,
                ]);
        }

        return [];
    }

    protected function getFtsTransferIdFromAttempts(PublicCollection  $attempts)
    {
        $ftsTransferIds = [];

        foreach ($attempts as $attempt)
        {
            $ftsTransferId = $attempt->getFtsTransferId();

            $ftsTransferIds[] = $ftsTransferId;
        }

        return implode("," ,$ftsTransferIds);
    }

    protected function extractFtsResponse(array $response)
    {
        $result = [];

        foreach ($response as $val)
        {

          $result[$val['source_id']] = $val;
        }

        return $result;
    }

    protected function combineStatusForApiAndFTS(PublicCollection $source, array $response)
    {
        $responseData = [];

        foreach ($source as $entity)
        {
            $responseData = $response[$entity->getId()];

            $responseData[$entity->getId()] = [
                'source_id'           => $entity->getId(),
                'source_status'       => $entity->getStatus(),
                'payout_remarks'      => $entity->getRemarks(),
                'fund_account_ID'     => $entity->getFundAccountId(),
                'merchant_ID'         => $entity->getMerchantId(),
                'method'              => $entity->getMethod(),
                'purpose'             => $entity->getPurpose(),
                'purpose_type'        => $entity->getPurposeType(),
                'amount'              => $entity->getAmount(),
                'status'              => $entity->getStatus(),
                'channel'             => $entity->getChannel(),
                'utr'                 => $entity->getUtr(),
                'return_utr'          => $entity->getReturnUtr(),
                'failure_reason'      => $entity->getFailureReason(),
                'fts_status'          => $responseData[Constants::STATUS],
                'fts_transfer_id'     => $responseData[Constants::FUND_TRANSFER_ID],
                'bank_status_code'    => $responseData[Constants::BANK_STATUS_CODE],
                'fts_channel'         => $responseData[Constants::CHANNEL],
                'fts_fund_account_ID' => $responseData[Constants::FUND_ACCOUNT_ID],
                'source_account_ID'   => $responseData[Constants::SOURCE_ACCOUNT_ID],
                'gateway_error_code'  => $responseData[Constants::GATEWAY_ERROR_CODE],
                'fts_utr'             => $responseData[Constants::UTR],
                'fts_return_utr'      => $responseData[Constants::RETURN_UTR],
                'fts_failure_reason'  => $responseData[Constants::FAILURE_REASON],
                'fts_remarks'         => $responseData[Constants::REMARKS],
             ];
        }

        return $responseData;
    }

    /**
     * Sends Alert to FTS from dashboard
     * Used for bank downtime and uptime manual detection from dashboard
     *
     * @param array $input
     * @return array
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    public function createChannelHealth(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::CHANNEL_HEALTH_EVENT,
            Requests::POST,
            $input);
    }

    public function deleteChannelHealth(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::CHANNEL_HEALTH_EVENT,
            Requests::DELETE,
            $input);
    }

    public function triggerTestTransactions(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FTS_TEST_TRANSACTIONS_TRIGGER,
            Requests::POST,
            $input);
    }

    public function getChannelHealthStats(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FTS_CHANNEL_HEALTH_EVENT_STATS,
            Requests::GET,
            $input);
    }

    public function getNewChannelHealthStats(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FTS_NEW_CHANNEL_HEALTH_STATS,
            Requests::GET,
            $input);
    }

    public function createSourceAccountMappings(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::SOURCE_ACCOUNT_MAPPING,
            Requests::POST,
            $input);
    }

    public function createDirectAccountRoutingRules(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::DIRECT_ACCOUNT_ROUTING_RULES,
            Requests::POST,
            $input);
    }

    public function deleteDirectAccountRoutingRules(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::DIRECT_ACCOUNT_ROUTING_RULES,
            Requests::DELETE,
            $input);
    }

    public function createSourceAccountCopy(array $input)
    {
        return $this->createAndSendRequest(
            parent::SOURCE_ACCOUNT_COPY,
            Requests::POST,
            $input);
    }

    public function createPreferredRoutingWeights(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::PREFERRED_ROUTING_WEIGHT,
            Requests::POST,
            $input);
    }

    public function createAccountTypeMappings(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::ACCOUNT_TYPE_MAPPING,
            Requests::POST,
            $input);
    }

    public function deleteSourceAccountMappings(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::SOURCE_ACCOUNT_MAPPING,
            Requests::DELETE,
            $input);
    }

    public function deletePreferredRoutingWeights(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::PREFERRED_ROUTING_WEIGHT,
            Requests::DELETE,
            $input);
    }

    public function deleteAccountTypeMappings(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::ACCOUNT_TYPE_MAPPING,
            Requests::DELETE,
            $input);
    }

    public function initiateBulkFtsAttempts(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_ATTEMPTS_INITIATE_URI,
            Requests::POST,
            $input);
    }

    public function publishBulkTransfers(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_PUBLISH_URI,
            Requests::POST,
            $input);
    }

    public function failQueuedTransfer(array $input)
    {
        return $this->createAndSendRequest(
            parent::FAIL_QUEUED_TRANSFER_URI,
            Requests::PATCH,
            $input);
    }

    public function failQueuedTransferBulk(array $input)
    {
        return $this->createAndSendRequest(
            parent::FAIL_QUEUED_TRANSFER_URI_BULK,
            Requests::PATCH,
            $input);
    }

    public function createSchedule(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::SCHEDULE,
            Requests::POST,
            $input);
    }

    public function deleteSchedule(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::SCHEDULE,
            Requests::DELETE,
            $input);
    }

    public function updateSchedule(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::SCHEDULE,
            Requests::PATCH,
            $input);
    }

    public function manualOverride(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::MANUAL_OVERRIDE,
            Requests::POST,
            $input);
    }

    public function createMerchantConfigurations(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FTS_MERCHANT_CONFIGURATIONS_URL,
            Requests::POST,
            $input);
    }

    public function deleteMerchantConfigurations(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FTS_MERCHANT_CONFIGURATIONS_URL,
            Requests::DELETE,
            $input);
    }

    public function patchMerchantConfigurations(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FTS_MERCHANT_CONFIGURATIONS_URL,
            Requests::PATCH,
            $input);
    }


    public function getHolidayDetails(array $input)
    {
        $this->trace->info(
            TraceCode::FTS_HOLIDAY_DEBUG,
            [
                "input" => $input,
            ]
        );


        return $this->createAndSendRequest(
            parent::FTS_HOLIDAY_URL,
            Requests::GET,
            $input);
    }

    public function failFastStatusManualUpdate(array $input)
    {
        $this->setAdminHeader();

        return $this->createAndSendRequest(
            parent::FTS_FAIL_FAST_STATUS_MANUAL_UPDATE,
            Requests::POST,
            $input);
    }
}
