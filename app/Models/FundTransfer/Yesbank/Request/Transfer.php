<?php

namespace RZP\Models\FundTransfer\Yesbank\Request;

use RZP\Models\Bank\IFSC;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Base as BaseModel;
use RZP\Models\Base\PublicEntity;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\Status;

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
     * {@inheritdoc}
     */
    public function requestBody(): string
    {
        $source = $this->entity->source;

        $amount = ($source->getAmount() / 100);

        return json_encode([
            Constants::TRANSFER_REQUEST_IDENTIFIER => [
                Constants::VERSION                      => self::VERSION,
                Constants::UNIQUE_REQUEST_NO            => $this->entity->getId(),
                Constants::APP_ID                       => $this->appId,
                Constants::PURPOSE_CODE                 => Constants::PURPOSE_CODE_MAP[$this->purpose],
                Constants::CUSTOMER_ID                  => $this->customerId,
                Constants::DEBIT_ACCOUNT_NUMBER         => $this->accountNumber,
                Constants::BENEFICIARY                  => $this->getPurposeSpecificData(),
                Constants::TRANSFER_TYPE                => $this->getPaymentType($this->entity->bankAccount, $amount),
                Constants::TRANSFER_CURRENCY_CODE       => Constants::DEFAULT_CURRENCY,
                Constants::TRANSFER_AMOUNT              => $amount,
                Constants::REMITTER_TO_BENEFICIARY_INFO => 'FUND TRANSFER',
            ],
        ]);
    }


    protected function getPaymentType(BankAccount\Entity $ba, $amount)
    {
        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if ($ifscFirstFour === IFSC::YESB)
        {
            return Constants::FT;
        }
        else if ($amount < self::MAX_IMPS_AMOUNT)
        {
            return Mode::IMPS;
        }

        return $this->getTransferMode($amount, $ba->merchant);
    }

    protected function getPurposeSpecificData(): array
    {
        if ($this->isRefund() === true)
        {
            $beneName = $this->entity->bankAccount->getBeneficiaryName();

            // Remove all numbers from the name
            $normalizedName = $words = preg_replace('/\d+/', '', $beneName);

            // Name should have length between 5 - 35
            $beneName = (strlen($normalizedName) < 5) ? 'Not Available' : substr($normalizedName, 0, 35);

            return [
                Constants::BENEFICIARY_DETAILS => [
                    Constants::BENEFICIARY_NAME       => [
                        Constants::FULL_NAME => $beneName,
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

        $bankReferenceNo =  $response[Constants::UNIQUE_RESPONSE_NO] ?? null;

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
}
