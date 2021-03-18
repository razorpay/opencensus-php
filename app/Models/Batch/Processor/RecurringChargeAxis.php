<?php

namespace RZP\Models\Batch\Processor;

use RZP\Base\RuntimeManager;
use RZP\Models\Batch\Entity;

class RecurringChargeAxis extends Base
{
    protected $defaultEntries = [
        [
            'slno'               => '1',
            'URNNo'              => '11223344',
            'Folio_No'           => '91000xxxxxx',
            'SchemeCode'         => 'AF',
            'TransactionNo'      => '86XXX',
            'InvestorName'       => 'Srinivas M',
            'Purchase Day'       => '1',
            'Pur Amount'         => '1000',
            'BankAccountNo'      => '02951XXXXXXX',
            'Purchase Date'      => '2/15/21',
            'Batch Ref Number'   => '1',
            'Branch'             => 'RPXX',
            'Tr.Type'            => 'SIN',
            'UMRN No / TOKEN ID' => 'HDFC60000XXXXXXXX',
            'Credit Account No'  => '91602XXXXXX',
        ]
    ];

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->increaseAllowedSystemLimits();
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(3600);

    }

    protected function validateInputFileEntries(array $input): array
    {
        return $this->defaultEntries;
    }

    protected function shouldSkipValidateInputFile(): bool
    {
        return true;
    }
}
