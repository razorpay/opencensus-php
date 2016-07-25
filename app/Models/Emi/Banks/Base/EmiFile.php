<?php

namespace RZP\Models\Emi\Banks\Base;

use Str;
use Carbon\Carbon;
use RZP\Models\Card;
use RZP\Models\Settlement\Kotak\FileHandlerTrait;

class EmiFile
{
    use FileHandlerTrait;

    // Regenerated every time the EMI file is created
    protected $emiFilePassword;

    const EMI_FILE_PASSWORD_LENGTH = 7;

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

    protected function fetchAndSendPassword()
    {
        $this->emiFilePassword = $this->generateEmiFilePassword();

        $this->sendEmiPassword();
    }

    protected function generateEmiFilePassword()
    {
        return Str::random(self::EMI_FILE_PASSWORD_LENGTH);
    }

    protected function getZippedFile()
    {
        $fullPath = $this->getExcelFullFilePath();
        $fileArray = array($fullPath);

        $zipPath = $this->makeZipFile($fileArray, $this->emiFilePassword);

        return $zipPath;
    }
}
