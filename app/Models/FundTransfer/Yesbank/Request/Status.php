<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicEntity;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\GatewayStatus;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\Status as ValidStatus;

class Status extends Base
{
    const VERSION = 1;

    protected $entity;

    protected $requestTraceCode = TraceCode::NODAL_PAYMENT_STATUS_REQUEST;

    protected $responseTraceCode = TraceCode::NODAL_PAYMENT_STATUS_RESPONSE;

    protected $responseIdentifier = Constants::STATUS_RESPONSE_IDENTIFIER;

    public function __construct()
    {
        parent::__construct();

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
        return json_encode([
            Constants::STATUS_REQUEST_IDENTIFIER => [
                Constants::VERSION              => self::VERSION,
                Constants::CUSTOMER_ID          => $this->customerId,
                Constants::REQUEST_REFERENCE_NO => $this->entity->getId(),
            ],
        ]);
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

        return [
            self::PAYMENT_REF_NO       => $this->entity->getId(),
            self::UTR                  => $this->getNullOnEmpty($utr),
            self::BANK_STATUS_CODE     => $status,
            self::REMARK               => $this->getNullOnEmpty($remark),
            self::BANK_SUB_STATUS_CODE => $this->getNullOnEmpty($bankSubStatus),
            self::PAYMENT_DATE         => $this->getNullOnEmpty($paymentDate),
            self::TRANSFER_TYPE        => $transactionType,
            self::REFERENCE_NUMBER     => null,
            self::MODE                 => $mode,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFailedData(array $response): array
    {
        $remark = $response[Constants::REASON][Constants::TEXT] ?? null;

        $subCode = $response[Constants::CODE][Constants::SUB_CODE][Constants::VALUE] ?? null;

        return [
            self::PAYMENT_REF_NO       => $this->entity->getId(),
            self::UTR                  => null,
            self::BANK_STATUS_CODE     => ValidStatus::FAILED,
            self::REMARK               => $remark,
            self::BANK_SUB_STATUS_CODE => $subCode,
            self::PAYMENT_DATE         => null,
            self::TRANSFER_TYPE        => null,
            self::REFERENCE_NUMBER     => null,
            self::MODE                 => null,
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
        //     self::MODE

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
                Constants::VERSION                => "2.0",
                Constants::TRANSFER_TYPE          => Constants::DEFAULT_TRANSFER_TYPE,
                Constants::REQ_TRANSFER_TYPE      => Constants::DEFAULT_TRANSFER_TYPE,
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
    protected function mockGenerateFailedResponse(): string
    {
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
        // TODO: return stuff
        return [];
    }
}
