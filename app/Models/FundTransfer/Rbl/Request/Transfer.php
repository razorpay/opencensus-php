<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Settlement\Channel;
use RZP\Exception\RuntimeException;

class Transfer extends Base
{
    const BENE_CODE_PRIFIX = 'BENRZPAYP';

    protected $transferMode;

    protected $entity;

    protected $requestTraceCode  = TraceCode::RBL_NODAL_TRANSFER_REQUEST;

    protected $responseTraceCode = TraceCode::RBL_NODAL_TRANSFER_RESPONSE;

    public function __construct()
    {
        parent::__construct();

        $this->urlIdentifier = $this->config['fund_transfer_url_suffix'];
    }

    public function getMode()
    {
        return $this->transferMode;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function requestBody(): string
    {
        $source             = $this->entity->source;

        $amount             = ($source->getAmount() / 100);

        $beneId             = $this->entity->bankAccount->getId();

        $this->transferMode = $this->getTransferMode($amount);

        // Do not change the order of fields.
         return json_encode([
            'Single_Payment_Corp_Req' => [
                'Header' => [
                    'TranID'      => $this->entity->getId(),
                    'Corp_ID'     => self::CORP_ID,
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
                    'Ben_ID'               => self::BENE_CODE_PRIFIX . $beneId,
                ],
                'Signature' => [
                    'Signature' => 'Signature'
                ],
            ]
        ]);
    }
}
