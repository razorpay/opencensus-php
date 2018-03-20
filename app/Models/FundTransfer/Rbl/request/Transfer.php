<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Settlement\Channel;
use RZP\Exception\RuntimeException;

class Transfer extends Base
{
    /**
     * TODO: Should be removed once the bene issues resolved
     * Hardcoded list of bene code of other nodal accounts
     * This is because currently we have added 2 bene which are kotak and icici
     * and we are transferring only to the above banks of our nodal account.
     * these codes are not available in the table so we are keeping a map of bene code which we are currently supporting
     */
    const NODAL_BENE_CODE_MAP    = [
        Channel::ICICI  => 'BENRZPAYP9KnuHhe0P5eJgB',
        Channel::KOTAK  => 'BENRZPAYP9KnioczXfED3wz',
//        Channel::AXIS   => 'BENRZPAYP9KnioczXfED3wz' // TODO: Add Axis bank bene code
    ];

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

    public function requestBody(): array
    {
        $source             = $this->entity->source;

        $amount             = ($source->getAmount() / 100);

        $channel            = $this->getDestinationChannel();

        $this->transferMode = $this->getTransferMode($amount);

         return [
            'Single_Payment_Corp_Req' => [
                'Header' => [
                    'TranID'      => $this->entity->getId(),
                    'Corp_ID'     => self::CORP_ID,
                    'Maker_ID'    => self::MAKER_ID,
                    'Checker_ID'  => self::CHECKER_ID,
                    'Approver_ID' => self::APPROVER_ID,
                ],
                'Body' => [
                    'Ben_ID'               => self::NODAL_BENE_CODE_MAP[$channel],
                    'Amount'               => $amount,
                    'Remarks'              => 'Transfer',
                    'Mode_of_Pay'          => $this->transferMode,
                    'Debit_Acct_No'        => $this->accountNumber,
                    'Debit_Acct_Name'      => self::ACCOUNT_NAME,
                    'Debit_PartTrnRmks'    => '',
                    'Debit_TrnParticulars' => 'Nodal to nodal',
                ],
                'Signature' => [
                    'Signature' => 'Signature'
                ],
            ]
        ];
    }

    /**
     * TODO: Should be removed once the bene issues resolved
     * Determining destination bank by using its IFSC code.
     * Currently we are supporting only nodal to nodal transfer from RBL
     * Where we have added only 2 bene which are Kotak and ICICI.
     * So we check the IFSC code to determine destination bank.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getDestinationChannel(): string
    {
        $ba       = $this->entity->bankAccount;

        $ifscCode = $ba->getIfscCode();

        $ifscFirstFour = strtoupper(substr($ifscCode, 0, 4));

        switch ($ifscFirstFour)
        {
            case IFSC::KKBK:
                return Channel::KOTAK;

            case IFSC::ICIC:
                return Channel::ICICI;

            default:
                throw new RuntimeException('Destination channel is not supported');
        }
    }
}
