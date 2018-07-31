<?php

namespace RZP\Gateway\Netbanking\Corporation\Mock;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Gateway\Base\Mock;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;

class Reconciliator extends Mock\Reconciliator
{
    const MERCHANT_CODE      = 'merchant_code';

    const TXN_EXECUTED_DATE  = 'txn_executed_date';

    const BANK_TXN_ID        = 'bank_txn_id';

    const MERCHANT_TXN_ID    = 'merchant_txn_id';

    const TXN_ORG_AMOUNT     = 'txn_org_amount';

    const STATUS             = 'status';

    public function __construct()
    {
        $this->gateway = Gateway::NETBANKING_CORPORATION;

        $this->fileExtension = 'txt';

        $this->fileToWriteName = '12345' . Carbon::now(Timezone::IST) . 'CORPBANK';

        parent::__construct();
    }

    protected function getEntitiesToReconcile()
    {
        return ($this->repo
                    ->payment
                    ->fetch(
                        ['gateway' => $this->gateway]));
    }

    protected function getReconciliationData(array $input)
    {
        $data = [];

        $data[] = $this->getHeaders();

        $payment = $input[0]['payment'];

        $data[] = [
            '12345',
            Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('dmY'),
            $payment['reference1'],
            $payment['id'],
            number_format($payment['amount']/100, 2, '.', ''),
            'S',
        ];

        return $this->generateText($data, '|');
    }

    //Overriding this base class create file as it creates and excel file
    protected function createFile(
        $content,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension($this->fileExtension)
            ->content($content)
            ->name($this->fileToWriteName)
            ->store($store)
            ->type($type)
            ->save();

        return $creator;
    }


    protected function getHeaders()
    {
       return  [
           self::MERCHANT_CODE,
           self::TXN_EXECUTED_DATE,
           self::BANK_TXN_ID,
           self::MERCHANT_TXN_ID,
           self::TXN_ORG_AMOUNT,
           self::STATUS,
       ];
    }
}
