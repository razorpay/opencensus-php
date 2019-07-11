<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicEntity;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Rbl\Reconciliation\Status as ValidStatus;

class Transfer extends Base
{
    const BENE_CODE_PRIFIX = 'BEN';

    protected $entity       = null;

    protected $transferMode = null;

    protected $requestTraceCode   = TraceCode::NODAL_TRANSFER_REQUEST;

    protected $responseTraceCode  = TraceCode::NODAL_TRANSFER_RESPONSE;

    protected $responseIdentifier = Constants::TRANSFER_RESPONSE_IDENTIFIER;

    public function __construct(string $purpose)
    {
        parent::__construct($purpose);

        $this->urlIdentifier = $this->config['fund_transfer_url_suffix'];
    }

    public function getMode()
    {
        return $this->transferMode;
    }

    public function init()
    {
        parent::init();

        $this->entity       = null;

        $this->transferMode = null;

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
     * {@inheritdoc}
     */
    public function requestBody(): string
    {
        $source             = $this->entity->source;

        $amount             = ($source->getAmount() / 100);

        $beneId             = $this->entity->bankAccount->getId();

        $this->transferMode = $this->getTransferMode($amount, $this->entity->bankAccount->merchant);

        // Do not change the order of fields.
         return json_encode([
            'Single_Payment_Corp_Req' => [
                'Header' => [
                    'TranID'      => $this->entity->getId(),
                    'Corp_ID'     => $this->corpId,
                    'Maker_ID'    => self::MAKER_ID,
                    'Checker_ID'  => self::CHECKER_ID,
                    'Approver_ID' => self::APPROVER_ID,
                ],
                'Body' => [
                    'Amount'               => (string) $amount,
                    'Debit_Acct_No'        => (string) $this->accountNumber,
                    'Debit_Acct_Name'      => self::ACCOUNT_NAME,
                    'Debit_TrnParticulars' => 'Nodal to nodal',
                    'Debit_PartTrnRmks'    => '',
                    'Mode_of_Pay'          => $this->transferMode,
                    'Remarks'              => 'Transfer',
                    'Ben_ID'               => self::BENE_CODE_PRIFIX . $this->corpId . $beneId,
                ],
                'Signature' => [
                    'Signature' => 'Signature'
                ],
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractSuccessfulData(array $response): array
    {
        $bankStatus = $response['Header']['Status'];

        $utr = $response['Body']['UTRNo'] ?? null;

        $rrn = $response['Body']['RRN No'] ?? null;

        $referenceNo = $response['Body']['RefNo'] ?? null;

        $transactionID = $response['Header']['TranID'];

        $now = Carbon::now(Timezone::IST)->format('Y-m-d H:i:s.u');

        $transactionTime = (empty($response['Body']['Txn_Time']) === true) ?
            $now : $response['Body']['Txn_Time'];

        $paymentDate = Carbon::createFromFormat(
            'Y-m-d H:i:s.u',
            $transactionTime,
            Timezone::IST)->getTimestamp();

        $utr = $this->getNullOnEmpty($utr);

        $rrn = $this->getNullOnEmpty($rrn);

        $utr = $this->getUtr($utr, $rrn);

        $data = [
            self::PAYMENT_REF_NO   => $this->getNullOnEmpty($transactionID),
            self::BANK_STATUS_CODE => $this->getNullOnEmpty($bankStatus),
            self::PAYMENT_DATE     => $this->getNullOnEmpty($paymentDate),
            self::REFERENCE_NUMBER => $this->getNullOnEmpty($referenceNo),
            self::UTR              => $utr,
            self::REMARKS          => null,
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFailedData(array $response): array
    {
        $transactionID = $response['Header']['TranID'];

        $bankStatus = $response['Header']['Status'] ?? null;

        $remark = $response['Header']['Error_Desc'] ?? null;

        $publicFailureReason = ValidStatus::getPublicFailureReason($bankStatus, null);

        return [
            self::PAYMENT_REF_NO        => $this->getNullOnEmpty($transactionID),
            self::BANK_STATUS_CODE      => $this->getNullOnEmpty($bankStatus),
            self::PAYMENT_DATE          => null,
            self::REFERENCE_NUMBER      => null,
            self::UTR                   => null,
            self::REMARKS               => $this->getNullOnEmpty($remark),
            self::PUBLIC_FAILURE_REASON => $this->getNullOnEmpty($publicFailureReason)
        ];
    }

    protected function getUtr($utr, $rrn): string
    {
        return (in_array($this->transferMode, [Mode::RTGS, Mode::NEFT], true) === true) ?
                    $utr : $rrn;
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
                    'TranID'      => $this->entity->getId(),
                    'Corp_ID'     => $this->corpId,
                    'Maker_ID'    => self::MAKER_ID,
                    'Checker_ID'  => self::CHECKER_ID,
                    'Approver_ID' => self::APPROVER_ID,
                    'Status'      => array_random($status),
                    'Error_Cde'   => [],
                    'Error_Desc'  => []
                ],
                'Body'      => [
                    'RefNo'       => PublicEntity::generateUniqueId(),
                    'UTRNo'       => PublicEntity::generateUniqueId(),
                    'PONum'       => 'some number',
                    'Ben_Acct_No' => 'some account number',
                    'Amount'      => 'Some Amount',
                    'Txn_Time'    => Carbon::now(Timezone::IST)->format('Y-m-d H:i:s.u'),
                    'Ben_ID'      => 'some random ID'
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
        return json_encode([
            $this->responseIdentifier => [
                'Header'    => [
                    'TranID'      => $this->entity->getId(),
                    'Corp_ID'     => $this->corpId,
                    'Maker_ID'    => self::MAKER_ID,
                    'Checker_ID'  => self::CHECKER_ID,
                    'Approver_ID' => self::APPROVER_ID,
                    'Status'      => ValidStatus::FAILURE,
                    'Error_Cde'   => 'some code',
                    'Error_Desc'  => 'some description'
                ],
                'Signature' => [
                    'Signature' => 'Signature'
                ]
            ]
        ]);
    }
}
