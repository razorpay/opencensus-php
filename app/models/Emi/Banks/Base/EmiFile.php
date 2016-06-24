<?php

namespace Models\Emi\Banks\Base;

use ZipArchive;
use Carbon\Carbon;
use Models\Card;
use Models\Settlement\Kotak\FileHandlerTrait;

class EmiFile
{
    use FileHandlerTrait;

    public function __construct()
    {
        $this->mail = \Mail::getFacadeRoot();
    }

    public function generate($input)
    {
        ;
    }

    protected function getCardNumber($card)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = Card\Tokenex::getCardNumber($cardToken);

        return $cardNumber;
    }

    protected function getZippedFile()
    {
        $zipPath = $this->getZipFullFilePath();
        $fullPath = $this->getExcelFullFilePath();

        $password = \Config::get('applications.emi')['password'];

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFile($fullPath);
        $zip->setPassword($password);
        $zip->close();

        return $zipPath;
    }
}
