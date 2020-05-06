<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use Config;

use RZP\Trace\TraceCode;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Redaction;
use RZP\Models\Card\Entity as CardVault;
use RZP\Models\FundTransfer\Yesbank\Mode;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundTransfer\Yesbank\NodalAccount;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\Status;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\GatewayStatus;
use RZP\Models\FundTransfer\Base\Reconciliation\Constants as ReconConstants;

class Transfer extends Base
{
    const VERSION = '1';

    const IFSC_CODE  = 'YESB0000022';

    const DEFAULT_NETWORK = 'default_network';

    // VPA format based on card issuer
    const VPA_FORMAT = [
        Issuer::SCBL => [
             Network::AMEX => 'AEBC%s@sc',
            self::DEFAULT_NETWORK => 'AEBC%s@sc',
        ],
        Issuer::ICIC => [
            Network::AMEX         => 'ccpay.0%s@icici',
            self::DEFAULT_NETWORK => 'CCPAY.%s@icici',
        ]
    ];

    protected $requestType;

    protected $entity = null;

    public $transferType = '';

    public $typesWithoutPurposeCode = false;

    protected $requestTraceCode = TraceCode::NODAL_TRANSFER_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_TRANSFER_RESPONSE;

    public function __construct(string $purpose, string $type = null, $useCurrentAccount = false)
    {
        parent::__construct($type);

        if ($useCurrentAccount === true)
        {
            $this->channel = Channel::YESBANK;

            $this->config = Config::get('nodal.yesbank.banking_ca');

            $this->appId = $this->config['app_id'];

            $this->accountNumber = $this->config['account_number'];

            $this->baseUrl = $this->config['url'];

            $this->appId = $this->config['app_id'];

            $this->customerId = $this->config['customer_id'];

            $this->method = 'POST';

            $this->version = '1';

            $this->init();
        }

        $this->requestType = $type;

        $this->purpose = $purpose;

        $this->urlIdentifier = $this->config['fund_transfer_url_suffix'];

        $this->typesWithoutPurposeCode = (in_array($type, [Attempt\Type::BANKING, Attempt\Type::SYNC], true) === true);

        if (($type === Attempt\Type::BANKING) and ($useCurrentAccount === false))
        {
            $this->typesWithoutPurposeCode = false;
        }

        $this->setRequestResponseIdentifiers();
    }

    public function init()
    {
        parent::init();

        $this->entity = null;

        return $this;
    }

    /**
     * 'serialize_precision' is set to -1 due to issue in json_encode while handling floating point numbers in php 7.1.
     * Refer following links.
     * https://bugs.php.net/bug.php?id=72567
     * https://stackoverflow.com/questions/42981409/php7-1-json-encode-float-issue
     * {@inheritdoc}
     */
    public function requestBody(): string
    {
        ini_set('serialize_precision', -1);

        $requestData = $this->getRequestData();

        $this->requestTrace = (new Redaction())->redactData($requestData);

        if ($this->isLogEnabled() === false and
            ($this->entity->isRefund() === true))
        {
            $this->requestTrace[$this->requestIdentifier]
            [Constants::BENEFICIARY]
            [Constants::BENEFICIARY_DETAILS]
            [Constants::BENEFICIARY_ACCOUNT_NO]
                = mask_except_last4($this->requestTrace
                                           [$this->requestIdentifier]
                                           [Constants::BENEFICIARY]
                                           [Constants::BENEFICIARY_DETAILS]
                                           [Constants::BENEFICIARY_ACCOUNT_NO],
                            'x'
                  );
        }

        $jsonRequest  = json_encode($requestData);

        ini_restore('serialize_precision');

        return $jsonRequest;
    }

    public function getMaskedAccountNumber()
    {
        return mask_except_last4($this->accountNumber);
    }

    protected function setRequestResponseIdentifiers()
    {
        switch ($this->requestType)
        {
            case Attempt\Type::SYNC:
                $this->requestIdentifier  = Constants::SYNC_TRANSFER_REQUEST_IDENTIFIER;
                $this->responseIdentifier = Constants::SYNC_TRANSFER_RESPONSE_IDENTIFIER;
                break;

            default:
                $this->requestIdentifier  = Constants::ASYNC_TRANSFER_REQUEST_IDENTIFIER;
                $this->responseIdentifier = Constants::ASYNC_TRANSFER_RESPONSE_IDENTIFIER;
                break;
        }
    }

    /**
     * Gives an request body in array format which has to be sent in request body
     * this will construct the data based on type of request
     * which is derived by entity
     *
     * @return array
     */
    protected function getRequestData(): array
    {
        $amount = $this->getFormattedAmount();

        $this->transferType = $this->getPaymentType($this->entity, $amount);

        $data = [
            $this->requestIdentifier => [
                Constants::VERSION                      => self::VERSION,
                Constants::UNIQUE_REQUEST_NO            => $this->entity->getId(),
                Constants::APP_ID                       => $this->appId,
                // This should be in this place else the request will fail
                Constants::PURPOSE_CODE                 => Constants::PURPOSE_CODE_MAP[$this->purpose],
                Constants::CUSTOMER_ID                  => $this->customerId,
                Constants::DEBIT_ACCOUNT_NUMBER         => $this->accountNumber,
                Constants::BENEFICIARY                  => $this->getPurposeSpecificData(),
                Constants::TRANSFER_TYPE                => $this->transferType,
                Constants::TRANSFER_CURRENCY_CODE       => Constants::DEFAULT_CURRENCY,
                Constants::TRANSFER_AMOUNT              => $amount,
                Constants::REMITTER_TO_BENEFICIARY_INFO => $this->getNarration($this->entity),
            ],
        ];

        //
        // Purpose is required for transfers from nodal accounts
        //
        if ($this->typesWithoutPurposeCode === true)
        {
            unset($data[$this->requestIdentifier][Constants::PURPOSE_CODE]);
        }

        return $data;
    }

    public function getRequestInputForGateway(): array
    {
        $fta = $this->entity;

        $source = $fta->source;

        $amount = $source->getAmount();

        //
        // In case of refunds - we need to use base amount
        // since there could be payments of international currencies and in FTA we are always using INR
        //
        if ($fta->getSourceType() === ConstantsEntity::REFUND)
        {
            $amount = $source->getBaseAmount();
        }

        //
        // For now, we would be hardcoding the terminal. Later, have to
        // figure out how to do terminal selection for this, since each
        // merchant might have a different terminal. Use-case being merchant
        // wants the payout/refund to happen from their custom vpa handle
        // instead of from razorpay handle
        //
        $terminal = $this->repo->terminal->findByGatewayAndTerminalData(Gateway::UPI_YESBANK);

        if ($fta->hasCard() === true)
        {
            $cardObj = $fta->card;

            $vaultToken = $this->getCardVaultToken($cardObj);

            $cardNum = $this->app['card.cardVault']->detokenize($vaultToken);

            $vpa = $this->getVpaHandleFromCardDetails($cardObj, $cardNum);
        }
        else
        {
            $vpa = $fta->vpa->getAddress();
        }

        $gatewayRequest = [
            'terminal' => $terminal->toArray(),
            'merchant' => $source->merchant->toArrayPublic(),
            'gateway_input' => [
                'amount'         => $amount,
                'vpa'            => $vpa,
                'ref_id'         => $fta->getId(),
                'narration'      => $this->getNarration($fta),
                'account_number' => $this->accountNumber,
                'ifsc_code'      => self::IFSC_CODE,
            ]
        ];

        if ($this->isLogEnabled() === false)
        {
            $this->requestTrace = $gatewayRequest;

            $this->requestTrace['gateway_input']['vpa'] = mask_except_last4(
                $this->requestTrace['gateway_input']['vpa'],
                'x');
        }

        return $gatewayRequest;
    }

    public function getActionForGateway(): string
    {
        return Action::PAYOUT;
    }

    /**
     * This will convert the amount which is in paise to rupees.
     * and log outpput of each stage for debugging purpose
     *
     * @return float|int
     */
    protected function getFormattedAmount()
    {
        $source = $this->entity->source;

        $sourceAmount = $source->getAmount();

        //
        // In case of refunds - we need to use base amount
        // since there could be payments of international currencies and in FTA we are always using INR
        //
        if ($this->entity->getSourceType() === ConstantsEntity::REFUND)
        {
            $sourceAmount = $source->getBaseAmount();
        }

        $this->trace->info(
            TraceCode::YESBANK_SOURCE_AMOUNT,
            [
                'sourceAmount' => $sourceAmount
            ]);

        $amount = ($sourceAmount / 100);

        $this->trace->info(
            TraceCode::YESBANK_CONVERTED_AMOUNT,
            [
                'convertedAmount' => $amount
            ]);

        $amount = round($amount, 2);

        $this->trace->info(
            TraceCode::YESBANK_TRANSFER_AMOUNT,
            [
                'transferAmount' => $amount
            ]);

        return $amount;
    }

    protected function getPaymentType(Attempt\Entity $attempt, $amount)
    {
        // Penny test is only possible through IMPS
        if ($attempt->isPennyTesting() === true)
        {
            return Mode::IMPS;
        }

        $nodalAccount = new NodalAccount;

        if ($attempt->hasCard() === true)
        {
            $mode = $nodalAccount->getPaymentModeForCard($attempt, $amount);
        }
        else
        {
            $mode = $nodalAccount->getPaymentModeForBankAccount($attempt, $amount);
        }

        return Mode::getExternalModeFromInternalMode($mode);
    }

    protected function getPurposeSpecificData(): array
    {
        $attempt = $this->entity;

        if (($attempt->isRefund() === true) and
            ($attempt->hasCard() === true))
        {
            return $this->fetchCardInfoAndPurposeData();
        }

        // Beneficiary details are required when the request is of purpose `refund` or
        // the request has to be made using sync API
        if (($attempt->isRefund() === true) or
            ($this->typesWithoutPurposeCode === true) or
            ($attempt->isPennyTesting() === true))
        {
            $beneName = $this->entity->bankAccount->getBeneficiaryName();

            $normalizedName = $this->normalizeBeneficiaryName($beneName);

            return [
                Constants::BENEFICIARY_DETAILS => [
                    Constants::BENEFICIARY_NAME       => [
                        Constants::FULL_NAME => $normalizedName,
                    ],
                    Constants::BENEFICIARY_CONTACT    => json_decode('{}'),
                    Constants::BENEFICIARY_ACCOUNT_NO => $this->entity->bankAccount->getAccountNumber(),
                    Constants::BENEFICIARY_IFSC       => $this->entity->bankAccount->getIfscCode(),
                ],
            ];
        }

        //
        // If not refund then beneficiary has to be registered
        // For Settlement and Payout will need beneficiary id for transfer
        //
        if (($attempt->hasCard() === true) and
            ($attempt->isSettlement() === true))
        {
            return [
                Constants::BENEFICIARY_CODE => 'card' . $this->entity->card->getId(),
            ];
        }

        return [
            Constants::BENEFICIARY_CODE => $this->entity->bankAccount->getId(),
        ];
    }

    /**
     * Rules:
     * - Min: 2 characters
     * - Max: 120 characters
     * - Regex: [\w\s]
     *
     * @param Attempt\Entity $fta
     *
     * @return string
     */
    protected function getNarration(Attempt\Entity $fta)
    {
        $ftaNarration = $fta->getNarration();

        if (empty($ftaNarration) === false)
        {
            $narration = $ftaNarration;
        }
        else
        {
            $narration = $fta->merchant->getBillingLabel();
        }

        $formattedNarration = preg_replace('/[^a-zA-Z0-9 ]+/', '', $narration);

        $formattedNarration = ($formattedNarration ? str_limit($formattedNarration, 30) : 'Razorpay');

        $formattedNarration = $formattedNarration . ' FUND TRANSFER';

        return $formattedNarration;
    }

    /**
     * @param $cardObj
     * @param $cardNum
     * @return string
     */
    public function getVpaHandleFromCardDetails($cardObj, $cardNum)
    {
        $issuer = $cardObj->getIssuer();

        $network = $cardObj->getNetworkCode();

        $networkList = self::VPA_FORMAT[$issuer]?: [];

        $vpaFormat = $networkList[self::DEFAULT_NETWORK];

        if (array_key_exists($network, $networkList) === true)
        {
            $vpaFormat = $networkList[$network];
        }

        $vpaHandle = sprintf($vpaFormat, $cardNum);

        return $vpaHandle;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractSuccessfulData(array $response): array
    {
        if ($this->requestType === Attempt\Type::SYNC)
        {
            return $this->extractDataFromSyncResponse($response);
        }

        return $this->extractDataFromAsyncResponse($response);
    }

    /**
     * For transfer request both success and failed response will follow the same pattern
     *
     * {@inheritdoc}
     */
    protected function extractFailedData(array $response): array
    {
        if ($this->requestType === Attempt\Type::SYNC)
        {
            return $this->extractDataFromSyncResponse($response);
        }

        return $this->extractDataFromAsyncResponse($response);
    }

    protected function extractDataFromSyncResponse(array $response): array
    {
        $rzpReferenceNo = $response[Constants::REQUEST_REFERENCE_NO] ?? null;

        $beneName = $response[Constants::NAME_WITH_BENEFICIARY_BANK] ?? null;

        $mode = $response[Constants::TRANSFER_TYPE] ?? null;

        $lowBalanceAlert = $response[Constants::LOW_BALANCE_ALERT] ?? null;

        $statusCode = $response[Constants::TRANSACTION_STATUS][Constants::STATUS_CODE] ?? null;

        $bankSubStatus = $response[Constants::TRANSACTION_STATUS][Constants::SUB_STATUS_CODE] ?? null;

        $bankReferenceNo = $response[Constants::TRANSACTION_STATUS][Constants::BANK_REFERENCE_NO] ?? null;

        $publicFailureReason = Status::getPublicFailureReason($statusCode, $bankSubStatus);

        $product = $this->entity->getSourceType();

        $isSuccess = Status::inStatus(Status::getSuccessfulStatus(), $statusCode, $bankSubStatus);

        $isFailure = Status::inStatus(Status::getFailureStatus(), $statusCode, $bankSubStatus);

        $mode = $this->entity->getMode();

        // capture failed response codes
        $this->captureBankStatusMetric(
            Channel::YESBANK,
            $product,
            $isFailure,
            $isSuccess,
            $mode,
            $statusCode,
            $bankSubStatus);

        return [
            ReconConstants::PAYMENT_REF_NO        => $this->getNullOnEmpty($rzpReferenceNo),
            ReconConstants::UTR                   => null,
            ReconConstants::BANK_STATUS_CODE      => $this->getNullOnEmpty($statusCode),
            ReconConstants::REMARKS               => null,
            ReconConstants::BANK_SUB_STATUS_CODE  => $this->getNullOnEmpty($bankSubStatus),
            ReconConstants::PAYMENT_DATE          => null,
            ReconConstants::TRANSFER_TYPE         => $mode,
            ReconConstants::REFERENCE_NUMBER      => $this->getNullOnEmpty($bankReferenceNo),
            ReconConstants::MODE                  => null,
            ReconConstants::PUBLIC_FAILURE_REASON => $this->getNullOnEmpty($publicFailureReason),
            ReconConstants::NAME_WITH_BENE_BANK   => $beneName,
            ReconConstants::LOW_BALANCE_ALERT     => $lowBalanceAlert,
            ReconConstants::REQUEST_FAILURE       => $this->isRequestFailure,
        ];
    }

    /**
     * Extract the required data from the given response array
     *
     * @param array $response
     * @return array
     */
    protected function extractDataFromAsyncResponse(array $response): array
    {
        $rzpReferenceNo = $response[Constants::REQUEST_REFERENCE_NO] ?? null;

        $bankReferenceNo = $response[Constants::BANK_REFERENCE_NO] ?? null;

        $statusCode = $response[Constants::STATUS_CODE] ?? null;

        $remark = $response[Constants::SUB_STATUS_TEXT] ?? null;

        $bankSubStatus = $response[Constants::SUB_STATUS_CODE] ?? null;

        $publicFailureReason = Status::getPublicFailureReason($statusCode, $bankSubStatus);

        $product = $this->entity->getSourceType();

        $isSuccess = Status::inStatus(Status::getSuccessfulStatus(), $statusCode, $bankSubStatus);

        $isFailure = Status::inStatus(Status::getFailureStatus(), $statusCode, $bankSubStatus);

        $mode = $this->entity->getMode();

        $this->captureBankStatusMetric(
            Channel::YESBANK,
            $product,
            $isFailure,
            $isSuccess,
            $mode,
            $statusCode,
            $bankSubStatus);

        if (($statusCode === GatewayStatus::STATUS_CODE_FAILURE) and ($bankSubStatus === GatewayStatus::DT))
        {
            try
            {
                $this->trace->critical(TraceCode::FTA_DUPLICATE_TRANSFER, $response);

                (new SlackNotification)->send(
                    'Duplicate Fund transfer',
                    [ 'response' => $rzpReferenceNo ],
                    null,
                    1,
                    'fts_alerts');
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::INFO,
                    TraceCode::FTA_RECON_ALERT_FAILED,
                    $response
                );
            }
        }

        return [
            ReconConstants::PAYMENT_REF_NO        => $this->getNullOnEmpty($rzpReferenceNo),
            ReconConstants::UTR                   => null,
            ReconConstants::BANK_STATUS_CODE      => $this->getNullOnEmpty($statusCode),
            ReconConstants::REMARKS               => $this->getNullOnEmpty($remark),
            ReconConstants::BANK_SUB_STATUS_CODE  => $this->getNullOnEmpty($bankSubStatus),
            ReconConstants::PAYMENT_DATE          => null,
            ReconConstants::TRANSFER_TYPE         => null,
            ReconConstants::REFERENCE_NUMBER      => $this->getNullOnEmpty($bankReferenceNo),
            ReconConstants::MODE                  => null,
            ReconConstants::PUBLIC_FAILURE_REASON => $this->getNullOnEmpty($publicFailureReason),
            ReconConstants::NAME_WITH_BENE_BANK   => null,
            ReconConstants::LOW_BALANCE_ALERT     => false,
            ReconConstants::REQUEST_FAILURE       => $this->isRequestFailure,
        ];
    }

    protected function extractGatewayData(array $response): array
    {
        // Required data:
        //     self::PAYMENT_REF_NO,
        //     self::UTR,
        //     self::BANK_STATUS_CODE,
        //     self::REMARK,
        //     self::PAYMENT_DATE,
        //     self::REFERENCE_NUMBER,
        //     self::MODE,
        //     self::PUBLIC_FAILURE_REASON

        //
        // We have null checks everywhere since it's possible that the
        // third-party is down and we don't get any data at all.
        //

        $ftaId = $response[Constants::UPI_REQUEST_REFERENCE_NUMBER] ?? null;

        $utr = $response[Constants::UPI_UNIQUE_RESPONSE_NUMBER] ?? null;
        $utr = (strtolower($utr) !== 'na') ? $utr : null;

        $bankReferenceNumber = $response[Constants::UPI_BANK_REFERENCE_NUMBER] ?? null;

        $statusCode = $response[Constants::UPI_STATUS_CODE] ?? null;

        $responseCode = $response[Constants::UPI_RESPONSE_CODE] ?? null;
        $errorCode = $response[Constants::UPI_ERROR_CODE] ?? null;
        $responseErrorCode = $response[Constants::UPI_RESPONSE_ERROR_CODE] ?? null;

        $finalResponseCode = GatewayStatus::getUsableCode($responseCode, $errorCode, $responseErrorCode);

        $remark = $response[Constants::UPI_STATUS_DESCRIPTION] ?? null;

        $publicFailureReason = GatewayStatus::getPublicFailureReason($finalResponseCode);

        $product = $this->entity->getSourceType();

        $isSuccess = GatewayStatus::inStatus(GatewayStatus::getSuccessfulStatus(), $statusCode, $finalResponseCode);

        $isFailure = GatewayStatus::inStatus(GatewayStatus::getFailureStatus(), $statusCode, $finalResponseCode);

        $mode = $this->entity->getMode();

        $this->captureBankStatusMetric(
            Channel::YESBANK,
            $product,
            $isFailure,
            $isSuccess,
            $mode,
            $statusCode,
            $finalResponseCode);

        return [
            ReconConstants::PAYMENT_REF_NO        => $this->getNullOnEmpty($ftaId),
            ReconConstants::UTR                   => $this->getNullOnEmpty($utr),
            ReconConstants::BANK_STATUS_CODE      => $this->getNullOnEmpty($statusCode),
            ReconConstants::BANK_SUB_STATUS_CODE  => $this->getNullOnEmpty($finalResponseCode),
            ReconConstants::REMARKS               => $this->getNullOnEmpty($remark),
            ReconConstants::PAYMENT_DATE          => null,
            ReconConstants::TRANSFER_TYPE         => null,
            ReconConstants::REFERENCE_NUMBER      => $this->getNullOnEmpty($bankReferenceNumber),
            ReconConstants::MODE                  => Mode::UPI,
            ReconConstants::PUBLIC_FAILURE_REASON => $this->getNullOnEmpty($publicFailureReason),
            ReconConstants::REQUEST_FAILURE       => $this->isRequestFailure,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGenerateSuccessResponse(): string
    {
        if ($this->requestType === Attempt\Type::SYNC)
        {
            return $this->generateSyncMockSuccessResponse();
        }

        return $this->generateAsyncMockSuccessResponse();
    }

    protected function generateSyncMockSuccessResponse(): string
    {
        return json_encode([
            Constants::SYNC_TRANSFER_RESPONSE_IDENTIFIER => [
                Constants::VERSION                      => self::VERSION,
                Constants::REQUEST_REFERENCE_NO         => $this->entity->getId(),
                Constants::NAME_WITH_BENEFICIARY_BANK   => 'Someone',
                Constants::LOW_BALANCE_ALERT            => false,
                Constants::TRANSFER_TYPE                => $this->transferType,
                Constants::ATTEMPT_NO                   => 1,
                Constants::UNIQUE_RESPONSE_NO           => PublicEntity::generateUniqueId(),
                Constants::TRANSACTION_STATUS           => [
                    Constants::STATUS_CODE              => Status::COMPLETED,
                    Constants::SUB_STATUS_CODE          => 0,
                    Constants::BANK_REFERENCE_NO        => PublicEntity::generateUniqueId(),
                    Constants::BENEFICIARY_REFERENCE_NO => json_decode('{}'),
                ]
            ],
        ]);
    }

    protected function generateAsyncMockSuccessResponse(): string
    {
        return json_encode([
            Constants::ASYNC_TRANSFER_RESPONSE_IDENTIFIER => [
                Constants::VERSION              => self::VERSION,
                Constants::REQUEST_REFERENCE_NO => $this->entity->getId(),
                Constants::UNIQUE_RESPONSE_NO   => PublicEntity::generateUniqueId(),
                Constants::REQ_TRANSFER_TYPE    => $this->transferType,
                Constants::STATUS_CODE          => Status::AS,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGenerateFailedResponse(string $failure = ''): string
    {
        if ($this->requestType === Attempt\Type::SYNC)
        {
            return $this->generateSyncMockFailureResponse();
        }

        return $this->generateAsyncMockFailureResponse();
    }

    protected function generateSyncMockFailureResponse(): string
    {
        if (($this->entity->source instanceof Entity) and
            ($this->entity->source->getReceipt() === 'failed_response_insufficient_funds'))
        {
            return $this->generateSyncMockFailureResponseForInsufficientFunds();
        }

        return $this->generateSyncMockFailureResponseForBeneficiaryNotAccepted();
    }

    protected function generateSyncMockFailureResponseForBeneficiaryNotAccepted(): string
    {
        return json_encode([
            Constants::SYNC_TRANSFER_RESPONSE_IDENTIFIER => [
                Constants::VERSION                      => self::VERSION,
                Constants::REQUEST_REFERENCE_NO         => $this->entity->getId(),
                Constants::NAME_WITH_BENEFICIARY_BANK   => '',
                Constants::LOW_BALANCE_ALERT            => false,
                Constants::TRANSFER_TYPE                => Mode::IMPS,
                Constants::ATTEMPT_NO                   => 1,
                Constants::UNIQUE_RESPONSE_NO           => PublicEntity::generateUniqueId(),
                Constants::TRANSACTION_STATUS           => [
                    Constants::STATUS_CODE              => Status::FAILED,
                    Constants::SUB_STATUS_CODE          => 'npci:E307',
                    Constants::BANK_REFERENCE_NO        => PublicEntity::generateUniqueId(),
                    Constants::BENEFICIARY_REFERENCE_NO => json_decode('{}'),
                ]
            ],
        ]);
    }

    protected function generateSyncMockFailureResponseForInsufficientFunds(): string
    {
        return json_encode([
            Constants::SYNC_TRANSFER_RESPONSE_IDENTIFIER => [
                Constants::VERSION                      => self::VERSION,
                Constants::REQUEST_REFERENCE_NO         => $this->entity->getId(),
                Constants::NAME_WITH_BENEFICIARY_BANK   => '',
                Constants::LOW_BALANCE_ALERT            => false,
                Constants::TRANSFER_TYPE                => Mode::IMPS,
                Constants::ATTEMPT_NO                   => 1,
                Constants::UNIQUE_RESPONSE_NO           => PublicEntity::generateUniqueId(),
                Constants::TRANSACTION_STATUS           => [
                    Constants::STATUS_CODE              => Status::FAILED,
                    Constants::SUB_STATUS_CODE          => 'ns:E402',
                    Constants::BANK_REFERENCE_NO        => PublicEntity::generateUniqueId(),
                    Constants::BENEFICIARY_REFERENCE_NO => json_decode('{}'),
                ]
            ],
        ]);
    }

    protected function generateAsyncMockFailureResponse(): string
    {
        return json_encode([
            Constants::ASYNC_TRANSFER_RESPONSE_IDENTIFIER => [
                Constants::VERSION              => self::VERSION,
                Constants::REQUEST_REFERENCE_NO => $this->entity->getId(),
                Constants::UNIQUE_RESPONSE_NO   => PublicEntity::generateUniqueId(),
                Constants::REQ_TRANSFER_TYPE    => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::STATUS_CODE          => Status::FAILED,
                Constants::SUB_STATUS_CODE      => 'somecode',
                Constants::SUB_STATUS_TEXT      => 'Some error while attempting the transfer',
            ],
        ]);
    }

    protected function mockGenerateSuccessResponseForGateway(): array
    {
        return [
            Constants::UPI_REQUEST_REFERENCE_NUMBER => $this->entity->getId(),
            Constants::UPI_UNIQUE_RESPONSE_NUMBER   => PublicEntity::generateUniqueId(),
            Constants::UPI_RESPONSE_CODE            => GatewayStatus::COMPLETED,
            Constants::UPI_STATUS_CODE              => GatewayStatus::STATUS_CODE_SUCCESS,
        ];
    }

    protected function mockGenerateFailedResponseForGateway(): array
    {
        return [
            Constants::UPI_REQUEST_REFERENCE_NUMBER => $this->entity->getId(),
            Constants::UPI_UNIQUE_RESPONSE_NUMBER   => PublicEntity::generateUniqueId(),
            Constants::UPI_RESPONSE_CODE            => GatewayStatus::E99,
            Constants::UPI_STATUS_CODE              => GatewayStatus::STATUS_CODE_FAILURE,
        ];
    }

    protected function fetchCardInfoAndPurposeData()
    {
        $cardObj = $this->entity->card;

        $networkCode = $cardObj->getNetworkCode();

        $vaultToken = $this->getCardVaultToken($cardObj);

        $response = $this->app['card.cardVault']->detokenize($vaultToken);

        if ($networkCode === Network::DICL)
        {
            $response = '00'.$response;
        }

        $beneName = $this->normalizeBeneficiaryName($cardObj->getName());

        return [
            Constants::BENEFICIARY_DETAILS => [
                Constants::BENEFICIARY_NAME       => [
                    Constants::FULL_NAME => $beneName,
                ],
                Constants::BENEFICIARY_CONTACT    => json_decode('{}'),
                Constants::BENEFICIARY_ACCOUNT_NO => $response,
                Constants::BENEFICIARY_IFSC       => $this->getIfscCodeUsingCardInfo($cardObj),
            ],
        ];
    }
}
