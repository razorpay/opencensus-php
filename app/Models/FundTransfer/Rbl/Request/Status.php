<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\RuntimeException;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Rbl\Reconciliation\Status as ValidStatus;

class Status extends Base
{
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
     * {@inheritdoc}
     *
     * @return self
     */
    public function setEntity(Attempt\Entity $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Creates request body for status request
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function requestBody(): string
    {
        return json_encode([
            'get_Single_Payment_Status_Corp_Req' => [
                'Header' => [
                    // This has to be unique for every request.
                    'TranID'      => time() . rand(0, 1000),
                    'Corp_ID'     => $this->corpId,
                    'Maker_ID'    => self::MAKER_ID,
                    'Checker_ID'  => self::CHECKER_ID,
                    'Approver_ID' => self::APPROVER_ID,
                ],
                'Body' => $this->prepareContentBody(),
                'Signature' => [
                    'Signature' => 'Signature'
                ],
            ]
        ]);
    }

    /**
     * Gives the content body for the request.
     *  - If exist reference number or org ID then content is constructed accordingly
     *
     * @return array
     *
     * @throws RuntimeException
     */
    protected function prepareContentBody(): array
    {
        $referenceNumber = $this->entity->getCmsRefNo();

        $transactionId = $this->entity->getId();

        if (empty($referenceNumber) === false)
        {
            return [
                'RefNo' => $referenceNumber
            ];
        }
        else if (empty($transactionId) === false)
        {
            return [
                'OrgTransactionID'  => $transactionId
            ];
        }

        throw new RuntimeException('No valid reference number for the payment is available');
    }

    /**
     * {@inheritdoc}
     */
    protected function extractSuccessfulData(array $response): array
    {
        $responseContent = $response['Body'];

        $utr = $responseContent['UTRNO'] ?? null;

        $referenceNo = $responseContent['REFNO'] ?? null;

        $bankStatus = $responseContent['TXNSTATUS'] ?? null;

        $remark = $responseContent['STATUSDESC'] ?? null;

        $transactionID = $responseContent['ORGTRANSACTIONID'] ?? null;

        $today = Carbon::now(Timezone::IST)->format('Y-m-d H:i:s.u');

        $transactionTime = (empty($responseContent['TXNTIME']) === true) ?
            $today : $responseContent['TXNTIME'];

        $paymentDate = Carbon::createFromFormat(
            'Y-m-d H:i:s.u',
            $transactionTime,
            Timezone::IST)->getTimestamp();

        return [
            self::UTR              => $this->getNullOnEmpty($utr),
            self::REMARKS          => $this->getNullOnEmpty($remark),
            self::PAYMENT_DATE     => $this->getNullOnEmpty($paymentDate),
            self::PAYMENT_REF_NO   => $this->getNullOnEmpty($transactionID),
            self::BANK_STATUS_CODE => $this->getNullOnEmpty($bankStatus),
            self::REFERENCE_NUMBER => $this->getNullOnEmpty($referenceNo),
        ];
    }

    /**
     * This will be called when the request is failed from banks side.
     * As this is the status request we are not changing any data except the remark of the attempt
     *
     * {@inheritdoc}
     */
    protected function extractFailedData(array $response): array
    {
        $remark          = $response['Header']['Error_Desc'];

        //
        // Here we use the entity id as payment reference id as transaction id response in response will be random
        // Using the same data FTA when we get the failed responses
        //
        return [
            self::PAYMENT_REF_NO   => $this->entity->getId(),
            self::BANK_STATUS_CODE => $this->entity->getBankStatusCode(),
            self::PAYMENT_DATE     => $this->entity->getDateTime(),
            self::REFERENCE_NUMBER => $this->entity->getCmsRefNo(),
            self::UTR              => $this->entity->getUtr(),
            self::REMARKS          => $remark,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGenerateSuccessResponse(): string
    {
        $status = array_keys(ValidStatus::getSuccessfulStatus());

        return json_encode([
            $this->responseIdentifier => [
                'Header'    => [
                    'TranID'      => rand(),
                    'Corp_ID'     => $this->corpId,
                    'Maker_ID'    => self::MAKER_ID,
                    'Checker_ID'  => self::CHECKER_ID,
                    'Approver_ID' => self::APPROVER_ID,
                    'Status'      => 'SUCCESS',
                    'Error_Cde'   => '',
                    'Error_Desc'  => ''
                ],
                'Body'      => [
                    'ORGTRANSACTIONID'  => $this->entity->getId(),
                    'AMOUNT'            => '10',
                    'REFNO'             => $this->entity->getCmsRefNo(),
                    'UTRNO'             => PublicEntity::generateUniqueId(),
                    'PONUM'             => 'some string',
                    'BEN_ACCT_NO'       => '1006211030035980',
                    'BENIFSC'           => 'CBIN0R10001',
                    'TXNSTATUS'         => array_random($status),
                    'STATUSDESC'        => '',
                    'BEN_CONF_RECEIVED' => 'N',
                    'TXNTIME'           => Carbon::now(Timezone::IST)->format('Y-m-d H:i:s.u')
                ],
                'Signature' => [
                    'Signature' => 'Signature'
                ]
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGenerateFailedResponse(): string
    {
        $status = ValidStatus::FAILURE;

        return json_encode([
            $this->responseIdentifier => [
                'Header'    => [
                    'TranID'      => rand(),
                    'Corp_ID'     => $this->corpId,
                    'Maker_ID'    => self::MAKER_ID,
                    'Checker_ID'  => self::CHECKER_ID,
                    'Approver_ID' => self::APPROVER_ID,
                    'Status'      => $status,
                    'Error_Cde'   => '',
                    'Error_Desc'  => 'Reconciliation'
                ],
            ]
        ]);
    }
}
