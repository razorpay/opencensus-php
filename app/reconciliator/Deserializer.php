<?php


namespace Reconciliator;


class Deserializer
{
    const EXCEL = 'excel';
    
    protected $modifier;
    
    public function __construct()
    {
        $this->modifier = new Modifier;
    }

    public function deserialize($fileDetails)
    {
        if ($fileDetails['file_type'] === self::EXCEL)
        {
            $this->modifier->convertExcelToCsv($fileDetails);
        }
    }
}