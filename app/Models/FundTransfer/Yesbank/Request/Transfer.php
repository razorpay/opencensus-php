<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\BankAccount;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base as BaseModel;
use RZP\Models\Base\PublicEntity;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Yesbank\Mode;
use RZP\Models\FundTransfer\Yesbank\NodalAccount;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\Status;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\GatewayStatus;

class Transfer extends Base
{
    const VERSION = 1;

    protected $entity = null;

    protected $requestTraceCode = TraceCode::NODAL_TRANSFER_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_TRANSFER_RESPONSE;

    protected $requestIdentifier;

    protected $responseIdentifier;

    public function __construct(string $purpose, string $type = null)
    {
        parent::__construct($type);

        $this->purpose = $purpose;

        $this->urlIdentifier = $this->config['fund_transfer_url_suffix'];

        $this->setRequestResponseIdentifiers($type);
    }

    protected function setRequestResponseIdentifiers(string $type = null)
    {
        switch ($type)
        {
            case Attempt\Type::PENNY_TESTING:
                $this->requestIdentifier  = Constants::SYNC_TRANSFER_REQUEST_IDENTIFIER;
                $this->responseIdentifier = Constants::SYNC_TRANSFER_RESPONSE_IDENTIFIER;
                break;

            default:
                $this->requestIdentifier  = Constants::ASYNC_TRANSFER_REQUEST_IDENTIFIER;
                $this->responseIdentifier = Constants::ASYNC_TRANSFER_RESPONSE_IDENTIFIER;
                break;

        }
    }

    public function init()
    {
        parent::init();

        $this->entity = null;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     */
    public function setEntity(BaseModel\Entity $entity): self
    {
        $this->entity = $entity;

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

        $jsonRequest  = json_encode($requestData);

        ini_restore('serialize_precision');

        return $jsonRequest;
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

        $data = [
            $this->requestIdentifier => [
                Constants::VERSION                      => self::VERSION,
                Constants::UNIQUE_REQUEST_NO            => $this->entity->getId(),
                Constants::APP_ID                       => $this->appId,
                Constants::CUSTOMER_ID                  => $this->customerId,
                Constants::DEBIT_ACCOUNT_NUMBER         => $this->accountNumber,
                Constants::BENEFICIARY                  => $this->getPurposeSpecificData(),
                Constants::TRANSFER_TYPE                => $this->getPaymentType($this->entity, $amount),
                Constants::TRANSFER_CURRENCY_CODE       => Constants::DEFAULT_CURRENCY,
                Constants::TRANSFER_AMOUNT              => $amount,
                Constants::REMITTER_TO_BENEFICIARY_INFO => $this->getNarration(),
            ],
        ];

        //
        // Purpose is not required for sync mode
        //
        if ($this->entity->isPennyTesting() !== true)
        {
            $data[Constants::PURPOSE_CODE] = Constants::PURPOSE_CODE_MAP[$this->purpose];
        }

        return $data;
    }

    public function getRequestInputForGateway(): array
    {
        $fta = $this->entity;

        $source = $fta->source;

        $amount = $source->getAmount();

        //
        // For now, we would be hardcoding the terminal. Later, have to
        // figure out how to do terminal selection for this, since each
        // merchant might have a different terminal. Use-case being merchant
        // wants the payout/refund to happen from their custom vpa handle
        // instead of from razorpay handle
        //
        $terminal = $this->repo->terminal->findByGatewayAndTerminalData(Gateway::UPI_YESBANK);

        return [
            'terminal' => $terminal->toArray(),
            'merchant' => $source->merchant->toArrayPublic(),
            'gateway_input' => [
                'amount'    => $amount,
                'vpa'       => $fta->vpa->getAddress(),
                'ref_id'    => $fta->getId(),
            ]
        ];
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

        $this->trace->info(
            TraceCode::YESBANK_SOURCE_AMOUNT,
            [
                'sourceAmount' => $source->getAmount()
            ]);

        $amount = ($source->getAmount() / 100);

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

        $mode = (new NodalAccount)->getPaymentModeForBankAccount($attempt, $amount);

        return Mode::getExternalModeFromInternalMode($mode);
    }

    protected function getPurposeSpecificData(): array
    {
        $attempt = $this->entity;

        if (($attempt->isRefund() === true) or
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
     * @return string
     */
    protected function getNarration()
    {
        $ftaNarration = $this->entity->getNarration();

        if (empty($ftaNarration) === false)
        {
            $narration = $ftaNarration;
        }
        else
        {
            $narration = $this->entity->merchant->getBillingLabel();
        }

        $formattedNarration = preg_replace('/[^a-zA-Z0-9 ]+/', '', $narration);

        $formattedNarration = ($formattedNarration ? str_limit($formattedNarration, 30) : 'Razorpay');

        $formattedNarration = $formattedNarration . ' FUND TRANSFER';

        return $formattedNarration;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractSuccessfulData(array $response): array
    {
        return $this->extractDataFromAsyncResponse($response);
    }

    /**
     * For transfer request both success and failed response will follow the same pattern
     *
     * {@inheritdoc}
     */
    protected function extractFailedData(array $response): array
    {
        if ($this->entity->isPennyTesting() === true)
        {
            return $this->extractDataFromSyncResponse($response);
        }
        else
        {
            return $this->extractDataFromAsyncResponse($response);
        }
    }

    protected function extractDataFromSyncResponse(array $response): array
    {
        $rzpReferenceNo = $response[Constants::REQUEST_REFERENCE_NO] ?? null;

        $bankReferenceNo = $response[Constants::BANK_REFERENCE_NO] ?? null;

        $statusCode = $response[Constants::STATUS_CODE] ?? null;

        $remark = $response[Constants::SUB_STATUS_TEXT] ?? null;

        $bankSubStatus = $response[Constants::SUB_STATUS_CODE] ?? null;

        $publicFailureReason = Status::getPublicFailureReason($bankSubStatus);

        return [
            self::PAYMENT_REF_NO       => $this->getNullOnEmpty($rzpReferenceNo),
            self::UTR                   => null,
            self::BANK_STATUS_CODE      => $this->getNullOnEmpty($statusCode),
            self::REMARK                => $this->getNullOnEmpty($remark),
            self::BANK_SUB_STATUS_CODE  => $this->getNullOnEmpty($bankSubStatus),
            self::PAYMENT_DATE          => null,
            self::TRANSFER_TYPE         => null,
            self::REFERENCE_NUMBER      => $this->getNullOnEmpty($bankReferenceNo),
            self::MODE                  => null,
            self::PUBLIC_FAILURE_REASON => $this->getNullOnEmpty($publicFailureReason),
            self::NAME_WITH_BENE_BANK   => null,
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

        $publicFailureReason = Status::getPublicFailureReason($bankSubStatus);

        return [
            self::PAYMENT_REF_NO       => $this->getNullOnEmpty($rzpReferenceNo),
            self::UTR                   => null,
            self::BANK_STATUS_CODE      => $this->getNullOnEmpty($statusCode),
            self::REMARK                => $this->getNullOnEmpty($remark),
            self::BANK_SUB_STATUS_CODE  => $this->getNullOnEmpty($bankSubStatus),
            self::PAYMENT_DATE          => null,
            self::TRANSFER_TYPE         => null,
            self::REFERENCE_NUMBER      => $this->getNullOnEmpty($bankReferenceNo),
            self::MODE                  => null,
            self::PUBLIC_FAILURE_REASON => $this->getNullOnEmpty($publicFailureReason),
            self::NAME_WITH_BENE_BANK   => null,
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

        // FTA ID
        $rzpReferenceNo = $response[Constants::REQUEST_REFERENCE_NO] ?? null;
        $utr = $response[Constants::UNIQUE_RESPONSE_NO] ?? null;
        $bankReferenceNo = $response[Constants::BANK_REFERENCE_NO] ?? null;

        $statusCode = $response[Constants::STATUS_CODE] ?? null;
        $bankSubStatus = $response[Constants::SUB_STATUS_CODE] ?? null;
        $remark = $response[Constants::SUB_STATUS_TEXT] ?? null;

        $publicFailureReason = GatewayStatus::getPublicFailureReason($statusCode);

        return [
            self::PAYMENT_REF_NO        => $this->getNullOnEmpty($rzpReferenceNo),
            self::UTR                   => $this->getNullOnEmpty($utr),
            self::BANK_STATUS_CODE      => $this->getNullOnEmpty($statusCode),
            self::REMARK                => $this->getNullOnEmpty($remark),
            self::BANK_SUB_STATUS_CODE  => $this->getNullOnEmpty($bankSubStatus),
            self::PAYMENT_DATE          => null,
            self::TRANSFER_TYPE         => null,
            self::REFERENCE_NUMBER      => $this->getNullOnEmpty($bankReferenceNo),
            self::MODE                  => null,
            self::PUBLIC_FAILURE_REASON => $this->getNullOnEmpty($publicFailureReason),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGenerateSuccessResponse(): string
    {
        return json_encode([
            Constants::ASYNC_TRANSFER_RESPONSE_IDENTIFIER => [
                Constants::VERSION              => self::VERSION,
                Constants::REQUEST_REFERENCE_NO => $this->entity->getId(),
                Constants::UNIQUE_RESPONSE_NO   => PublicEntity::generateUniqueId(),
                Constants::REQ_TRANSFER_TYPE    => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::STATUS_CODE          => Status::AS,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGenerateFailedResponse(): string
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
            Constants::REQUEST_REFERENCE_NO => $this->entity->getId(),
            Constants::UNIQUE_RESPONSE_NO   => PublicEntity::generateUniqueId(),
            Constants::STATUS_CODE          => GatewayStatus::COMPLETED,
        ];
    }

    protected function mockGenerateFailedResponseForGateway(): array
    {
        // TODO: Return stuff
        return [];
    }
}
