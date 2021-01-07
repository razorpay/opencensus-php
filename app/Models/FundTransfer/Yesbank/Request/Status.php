<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use Config;
use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicEntity;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\GatewayStatus;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\Status as ValidStatus;
use RZP\Models\FundTransfer\Base\Reconciliation\Constants as ReconConstants;

class Status extends Base
{
    const VERSION = 1;

    protected $entity;

    protected $requestTraceCode = TraceCode::NODAL_PAYMENT_STATUS_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_PAYMENT_STATUS_RESPONSE;

    protected $responseIdentifier = Constants::STATUS_RESPONSE_IDENTIFIER;

    public function __construct(string $type = null, $useCurrentAccount = false)
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

        $this->urlIdentifier = $this->config['payment_status_url_suffix'];
    }

    /**
     * Initializes the class variables
     *
     * @return $this
     */
    public function init()
    {
        parent::init();

        $this->entity = null;

        return $this;
    }

    /**
     * Creates request body for status request
     *
     * @return string
     */
    public function requestBody(): string
    {
        $body = [
            Constants::STATUS_REQUEST_IDENTIFIER => [
                Constants::VERSION              => self::VERSION,
                Constants::CUSTOMER_ID          => $this->customerId,
                Constants::REQUEST_REFERENCE_NO => $this->entity->getId(),
            ],
        ];

        $this->requestTrace = $body;

        return json_encode($body);
    }

    public function getRequestInputForGateway(): array
    {
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
            'gateway_input' => [
                'ref_id'    => $this->entity->getId(),
            ]
        ];
    }

    public function getActionForGateway(): string
    {
        return Action::PAYOUT_VERIFY;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractSuccessfulData(array $response): array
    {
        $transactionType = $response[Constants::TRANSFER_TYPE] ?? null;

        $paymentDate = $response[Constants::TRANSACTION_DATE] ?? null;

        $utr = $response[Constants::TRANSACTION_STATUS][Constants::BANK_REFERENCE_NO] ?? null;

        $statusCode = $response[Constants::TRANSACTION_STATUS][Constants::STATUS_CODE] ?? null;

        $bankSubStatus = $response[Constants::TRANSACTION_STATUS][Constants::SUB_STATUS_CODE] ?? null;

        $mode = $response[Constants::TRANSFER_TYPE] ?? null;

        $remark = ValidStatus::getRemark($bankSubStatus);

        $status = ValidStatus::getStatus($statusCode, $transactionType, $bankSubStatus);

        $beneName = $response[Constants::BENEFICIARY_NAME] ?? null;

        $product = $this->entity->getSourceType();

        $isSuccess = ValidStatus::inStatus(ValidStatus::getSuccessfulStatus(), $statusCode, $bankSubStatus);

        $isFailure = ValidStatus::inStatus(ValidStatus::getFailureStatus(), $statusCode, $bankSubStatus);

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
            ReconConstants::PAYMENT_REF_NO       => $this->entity->getId(),
            ReconConstants::UTR                  => $this->getNullOnEmpty($utr),
            ReconConstants::BANK_STATUS_CODE     => $status,
            ReconConstants::REMARKS              => $this->getNullOnEmpty($remark),
            ReconConstants::BANK_SUB_STATUS_CODE => $this->getNullOnEmpty($bankSubStatus),
            ReconConstants::PAYMENT_DATE         => $this->getNullOnEmpty($paymentDate),
            ReconConstants::TRANSFER_TYPE        => $transactionType,
            ReconConstants::REFERENCE_NUMBER     => null,
            ReconConstants::MODE                 => $mode,
            ReconConstants::NAME_WITH_BENE_BANK  => $beneName,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFailedData(array $response): array
    {
        $remark = $response[Constants::REASON][Constants::TEXT] ?? null;

        $subCode = $response[Constants::CODE][Constants::SUB_CODE][Constants::VALUE] ?? null;

        $product = $this->entity->getSourceType();

        $bankStatusCode = ($subCode === 'ns:E400') ? ValidStatus::PENDING : ValidStatus::FAILED;

        $isSuccess = ValidStatus::inStatus(ValidStatus::getSuccessfulStatus(), $bankStatusCode, $subCode);

        $isFailure = ValidStatus::inStatus(ValidStatus::getFailureStatus(), $bankStatusCode, $subCode);

        $mode = $this->entity->getMode();

        $this->captureBankStatusMetric(
            Channel::YESBANK,
            $product,
            $isFailure,
            $isSuccess,
            $mode,
            $bankStatusCode,
            $subCode);

        return [
            ReconConstants::PAYMENT_REF_NO       => $this->entity->getId(),
            ReconConstants::UTR                  => null,
            ReconConstants::BANK_STATUS_CODE     => $bankStatusCode,
            ReconConstants::REMARKS              => $remark,
            ReconConstants::BANK_SUB_STATUS_CODE => $subCode,
            ReconConstants::PAYMENT_DATE         => null,
            ReconConstants::TRANSFER_TYPE        => null,
            ReconConstants::REFERENCE_NUMBER     => null,
            ReconConstants::MODE                 => null,
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

        $publicFailureReason = GatewayStatus::getPublicFailureReason($statusCode, $finalResponseCode);

        $product = $this->entity->getSourceType();

        $isSuccess = ValidStatus::inStatus(ValidStatus::getSuccessfulStatus(), $statusCode, $finalResponseCode);

        $isFailure = ValidStatus::inStatus(ValidStatus::getFailureStatus(), $statusCode, $finalResponseCode);

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
        ];
    }

    public function getResponseDataFromFta(Attempt\Entity $fta)
    {
        $bankStatusCode = $fta->getBankStatusCode();

        $bankResponseCode = $fta->getBankResponseCode();

        $publicFailureReason = GatewayStatus::getPublicFailureReason($bankStatusCode, $bankResponseCode);

        return [
            ReconConstants::PAYMENT_REF_NO        => $fta->getId(),
            ReconConstants::UTR                   => $fta->getUtr(),
            ReconConstants::STATUS_CODE           => $fta->getBankResponseCode(),
            ReconConstants::BANK_STATUS_CODE      => $bankStatusCode,
            ReconConstants::REMARKS               => $fta->getRemarks(),
            ReconConstants::BANK_SUB_STATUS_CODE  => null,
            ReconConstants::PAYMENT_DATE          => null,
            ReconConstants::TRANSFER_TYPE         => null,
            ReconConstants::REFERENCE_NUMBER      => $fta->getCmsRefNo(),
            ReconConstants::MODE                  => $fta->getMode(),
            ReconConstants::PUBLIC_FAILURE_REASON => $this->getNullOnEmpty($publicFailureReason),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGenerateSuccessResponse(): string
    {
        $source = $this->entity->source;

        $amount = ($source->getAmount() / 100);

        return json_encode([
            $this->responseIdentifier => [
                Constants::VERSION                => '2.0',
                Constants::TRANSFER_TYPE          => $this->entity->getMode(),
                Constants::REQ_TRANSFER_TYPE      => $this->entity->getMode(),
                Constants::TRANSACTION_DATE       => Carbon::now(Timezone::IST)->format('Y-m-d H:i:s'),
                Constants::TRANSFER_AMOUNT        => $amount,
                Constants::TRANSFER_CURRENCY_CODE => Constants::DEFAULT_CURRENCY,
                Constants::TRANSACTION_STATUS     => [
                    Constants::STATUS_CODE              => ValidStatus::COMPLETED,
                    Constants::SUB_STATUS_CODE          => null,
                    Constants::BANK_REFERENCE_NO        => PublicEntity::generateUniqueId(),
                    Constants::BENEFICIARY_REFERENCE_NO => PublicEntity::generateUniqueId(),
                ],
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGenerateFailedResponse(string $failure = ''): string
    {
        if (($this->entity->source instanceof Entity) and
            ($this->entity->source->getReceipt() === 'failed_response_insufficient_funds'))
        {
            return $this->generateSyncMockFailureResponseForInsufficientFunds();
        }

        if (($this->entity->source instanceof Entity) and
            ($this->entity->source->getReceipt() === 'failed_response_beneficiary_not_accepted'))
        {
            return $this->generateSyncMockFailureResponseForBeneficiaryNotAccepted();
        }

        if (($this->entity->source instanceof Entity) and
            ($this->entity->source->getReceipt() === 'failed_resp_beneficiary_details_invalid'))
        {
            return $this->generateSyncMockFailureResponseForBeneficiaryDetailsInvalid();
        }

        if ($failure === 'merchant_error')
        {
            return $this->generateMerchantFailureResponse();
        }

        return json_encode([
            Constants::FAULT_RESPONSE_IDENTIFIER => [
                Constants::CODE   => [
                    Constants::VALUE    => 'soapenv12:Sender',
                    Constants::SUB_CODE => [
                        Constants::VALUE => 'ns:E403',
                    ],
                ],
                Constants::REASON => [
                    Constants::TEXT => 'Forbidden: The identity provided does not have the required authority',
                ],
            ],
        ]);
    }

    protected function generateSyncMockFailureResponseForInsufficientFunds(): string
    {
        $source = $this->entity->source;

        $amount = ($source->getAmount() / 100);

        return json_encode([
            $this->responseIdentifier => [
                Constants::VERSION                => '2.0',
                Constants::TRANSFER_TYPE          => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::REQ_TRANSFER_TYPE      => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::TRANSACTION_DATE       => Carbon::now(Timezone::IST)->format('Y-m-d H:i:s'),
                Constants::TRANSFER_AMOUNT        => $amount,
                Constants::TRANSFER_CURRENCY_CODE => Constants::DEFAULT_CURRENCY,
                Constants::TRANSACTION_STATUS     => [
                    Constants::STATUS_CODE              => ValidStatus::FAILED,
                    Constants::SUB_STATUS_CODE          => 'ns:E402',
                    Constants::BANK_REFERENCE_NO        => PublicEntity::generateUniqueId(),
                    Constants::BENEFICIARY_REFERENCE_NO => PublicEntity::generateUniqueId(),
                ],
            ],
        ]);
    }


    protected function generateSyncMockFailureResponseForBeneficiaryDetailsInvalid(): string
    {
        $source = $this->entity->source;

        $amount = ($source->getAmount() / 100);

        return json_encode([
            $this->responseIdentifier => [
                Constants::VERSION                => '2.0',
                Constants::TRANSFER_TYPE          => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::REQ_TRANSFER_TYPE      => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::TRANSACTION_DATE       => Carbon::now(Timezone::IST)->format('Y-m-d H:i:s'),
                Constants::TRANSFER_AMOUNT        => $amount,
                Constants::TRANSFER_CURRENCY_CODE => Constants::DEFAULT_CURRENCY,
                Constants::TRANSACTION_STATUS     => [
                    Constants::STATUS_CODE              => ValidStatus::INVALID_BENEFICIARY_DETAILS,
                    Constants::SUB_STATUS_CODE          => 'npci:E200',
                    Constants::BANK_REFERENCE_NO        => PublicEntity::generateUniqueId(),
                    Constants::BENEFICIARY_REFERENCE_NO => PublicEntity::generateUniqueId(),
                ],
            ],
        ]);
    }

    protected function generateSyncMockFailureResponseForBeneficiaryNotAccepted(): string
    {
        $source = $this->entity->source;

        $amount = ($source->getAmount() / 100);

        return json_encode([
            $this->responseIdentifier => [
                Constants::VERSION                => '2.0',
                Constants::TRANSFER_TYPE          => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::REQ_TRANSFER_TYPE      => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::TRANSACTION_DATE       => Carbon::now(Timezone::IST)->format('Y-m-d H:i:s'),
                Constants::TRANSFER_AMOUNT        => $amount,
                Constants::TRANSFER_CURRENCY_CODE => Constants::DEFAULT_CURRENCY,
                Constants::TRANSACTION_STATUS     => [
                    Constants::STATUS_CODE              => ValidStatus::FAILED,
                    Constants::SUB_STATUS_CODE          => 'npci:E307',
                    Constants::BANK_REFERENCE_NO        => PublicEntity::generateUniqueId(),
                    Constants::BENEFICIARY_REFERENCE_NO => PublicEntity::generateUniqueId(),
                ],
            ],
        ]);
    }

    protected function generateMerchantFailureResponse(): string
    {
        $source = $this->entity->source;

        $amount = ($source->getAmount() / 100);

        return json_encode([
            $this->responseIdentifier => [
                Constants::VERSION                => '2.0',
                Constants::TRANSFER_TYPE          => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::REQ_TRANSFER_TYPE      => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::TRANSACTION_DATE       => Carbon::now(Timezone::IST)->format('Y-m-d H:i:s'),
                Constants::TRANSFER_AMOUNT        => $amount,
                Constants::TRANSFER_CURRENCY_CODE => Constants::DEFAULT_CURRENCY,
                Constants::TRANSACTION_STATUS     => [
                    Constants::STATUS_CODE              => ValidStatus::FAILED,
                    Constants::SUB_STATUS_CODE          => 'npci:E449',
                    Constants::BANK_REFERENCE_NO        => PublicEntity::generateUniqueId(),
                    Constants::BENEFICIARY_REFERENCE_NO => PublicEntity::generateUniqueId(),
                ],
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
        // TODO: return stuff
        return [];
    }
}
