<?php

namespace RZP\Models\Emi\Banks\Sbin;

use RZP\Models\FileStore;
use RZP\Models\Emi\Banks\Base;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = '';

    protected $emailIdsToSendTo = ['sbicards.emi@razorpay.com'];

    protected $bankName = 'Sbi';

    protected $type = FileStore\Type::SBI_EMI_FILE_SFTP;

    protected $totalAmount;

    protected $totalTransactions;

    public function __construct()
    {
        parent::__construct();

        $this->transferMode = Base\EmiMode::SFTP;
    }

    protected function getEmiData($input)
    {
        $data = [];

        $totalAmount = 0;

        $totalTransactions = 0;

        foreach ($input as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount() / 100;

            $totalAmount = $totalAmount + $principalAmount;

            $totalTransactions++;


        }


    }
}
