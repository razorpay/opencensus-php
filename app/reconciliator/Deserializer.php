<?php


namespace Reconciliator;


class Deserializer
{
    protected $modifier;
    protected $extractor;

    protected $dataArray;
    
    public function __construct()
    {
        $this->modifier = new Modifier;
        $this->extractor = new Extractor;
    }

    public function deserialize($fileDetails)
    {
        $this->getDataArray($fileDetails);
        $this->extractor->extractDataArray($this->dataArray);
    }

    protected function getDataArray($fileDetails)
    {
        if ($fileDetails['file_type'] === Orchestrator::EXCEL)
        {
            // TODO: Handle multiple sheets in the workbook
            $this->dataArray = $this->modifier->convertExcelToArray($fileDetails);
        }
        else if ($fileDetails['file_type'] === Orchestrator::CSV)
        {
            $this->dataArray = $this->modifier->convertCsvToArray($fileDetails);
        }
        else
        {
            // TODO: Ideally, shouldn't come here. But, if it comes, throw an exception for unsupported type.
        }
    }

}