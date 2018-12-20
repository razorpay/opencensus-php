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

    protected $responseIdentifier = Constants::TRANSFER_RESPONSE_IDENTIFIER;

    public function __construct(string $purpose)
    {
        parent::__construct();

        $this->purpose = $purpose;

        $this->urlIdentifier = $this->config['fund_transfer_url_suffix'];
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
        $source = $this->entity->source;

        $this->trace->info(
            TraceCode::YESBANK_SOURCE_AMOUNT, ['sourceAmount' => $source->getAmount() ]);

        $amount = ($source->getAmount() / 100);

        $this->trace->info(
            TraceCode::YESBANK_CONVERTED_AMOUNT, ['convertedAmount' => $amount ]);

        $amount = round($amount, 2);

        ini_set('serialize_precision', -1);

        $this->trace->info(
            TraceCode::YESBANK_TRANSFER_AMOUNT, ['transferAmount' => $amount ]);

        $jsonRequest  = json_encode([
                Constants::TRANSFER_REQUEST_IDENTIFIER => [
                Constants::VERSION                      => self::VERSION,
                Constants::UNIQUE_REQUEST_NO            => $this->entity->getId(),
                Constants::APP_ID                       => $this->appId,
                Constants::PURPOSE_CODE                 => Constants::PURPOSE_CODE_MAP[$this->purpose],
                Constants::CUSTOMER_ID                  => $this->customerId,
                Constants::DEBIT_ACCOUNT_NUMBER         => $this->accountNumber,
                Constants::BENEFICIARY                  => $this->getPurposeSpecificData(),
                Constants::TRANSFER_TYPE                => $this->getPaymentType($this->entity, $amount),
                Constants::TRANSFER_CURRENCY_CODE       => Constants::DEFAULT_CURRENCY,
                Constants::TRANSFER_AMOUNT              => $amount,
                Constants::REMITTER_TO_BENEFICIARY_INFO => 'FUND TRANSFER',
            ],
        ]);

        ini_restore('serialize_precision');

        return $jsonRequest;
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

    protected function getPaymentType(Attempt\Entity $attempt, $amount)
    {
        $mode = (new NodalAccount)->getPaymentModeForBankAccount($attempt, $amount);

        return Mode::getExternalModeFromInternalMode($mode);
    }

    protected function getPurposeSpecificData(): array
    {
        if ($this->isRefund() === true)
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
     * {@inheritdoc}
     */
    protected function extractSuccessfulData(array $response): array
    {
        return $this->extractData($response);
    }

    /**
     * For transfer request both success and failed response will follow the same pattern
     *
     * {@inheritdoc}
     */
    protected function extractFailedData(array $response): array
    {
        return $this->extractData($response);
    }

    /**
     * Extract the required data from the given response array
     *
     * @param array $response
     * @return array
     */
    protected function extractData(array $response): array
    {
        $rzpReferenceNo = $response[Constants::REQUEST_REFERENCE_NO] ?? null;

        $bankReferenceNo =  $response[Constants::BANK_REFERENCE_NO] ?? null;

        $statusCode = $response[Constants::STATUS_CODE] ?? null;

        $remark = $response[Constants::SUB_STATUS_TEXT] ?? null;

        $bankSubStatus = $response[Constants::SUB_STATUS_CODE] ?? null;

        return [
            self::PAYMENT_REF_NO       => $this->getNullOnEmpty($rzpReferenceNo),
            self::UTR                  => null,
            self::BANK_STATUS_CODE     => $this->getNullOnEmpty($statusCode),
            self::REMARK               => $this->getNullOnEmpty($remark),
            self::BANK_SUB_STATUS_CODE => $this->getNullOnEmpty($bankSubStatus),
            self::PAYMENT_DATE         => null,
            self::TRANSFER_TYPE        => null,
            self::REFERENCE_NUMBER     => $this->getNullOnEmpty($bankReferenceNo),
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
        return json_encode([
            Constants::TRANSFER_RESPONSE_IDENTIFIER => [
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
            Constants::TRANSFER_RESPONSE_IDENTIFIER => [
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
