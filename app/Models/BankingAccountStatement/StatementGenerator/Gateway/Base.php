<?php

namespace RZP\Models\BankingAccountStatement\StatementGenerator\Gateway;

use RZP\Models\Base\Core as BaseCore;


abstract class Base extends BaseCore
{

    protected $accountNumber;
    protected $channel;

    public function __construct($accountNumber, $channel)
    {
        parent::__construct();

        $this->accountNumber = $accountNumber;
        $this->channel = $channel;
    }

//    public function generate($format, $data)
//    {
//        switch ($format) {
//            case "PDF":
//                return $this->pdf($data);
//                break;
//            case "CSV":
//                return $this->csv($data);
//                break;
//            case "XLSX":
//                return $this->xlsx($data);
//                break;
//        }
//    }

    abstract function pdf();

    abstract function csv();

    abstract function xlsx();


}
