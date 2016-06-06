<?php

namespace Reconciliator\HDFC;


use Reconciliator\Base;
use Reconciliator\Messenger;
use App;

class Reconciliate extends Base\Reconciliate
{
    protected $messenger;

    public function __construct()
    {
        $this->messenger = new Messenger();
    }

    protected function getTypeName()
    {
        return self::COMBINED;
    }


    public function inExcludeList($fileDetails)
    {
        if (strpos($fileDetails['file_name'], 'detailed') !== false)
        {
            return true;
        }

        return false;
    }

    public function getZipPassword($fileDetails)
    {
        $terminalId = explode('-', $fileDetails['file_name'])[0];

        $terminalRepo = App::getFacadeRoot()['repo']->terminal;

        $zipPassword = $terminalRepo->getById($terminalId)->getZipPassword();

        return $zipPassword;
    }
}