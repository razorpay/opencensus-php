<?php

namespace RZP\Models\Batch\Processor;
use RZP\Models\Merchant;
use RZP\Base\RuntimeManager;
use RZP\Models\Batch\Entity;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder as PhpSpreadsheetDefaultValueBinder;

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
        if ((new Merchant\Core)->isRazorxExperimentEnable($this->merchant->getId(),
                Merchant\RazorxTreatment::DUPLICATE_SHEET_VALIDATION_BATCH) === true)
        {
            $fileType = SpreadsheetIOFactory::identify($this->inputFileLocalPath);
            $reader = SpreadsheetIOFactory::createReader($fileType);
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheetDefaultValueBinder);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->inputFileLocalPath);
            assertTrue($spreadsheet->getSheetCount() === 1,"More than one Excel Sheet Found");
            return $this->defaultEntries;
        }
        return $this->defaultEntries;
    }

    protected function shouldSkipValidateInputFile(): bool
    {
        return true;
    }
}
