<?php

namespace Models\Emi\Banks\Base;

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
        $fullPath = $this->getExcelFullFilePath();
        $fileArray = array($fullPath);

        $password = \Config::get('applications.emi')['password'];

        $zipPath = $this->makeZipFile($fileArray, $password);

        return $zipPath;
    }
}
