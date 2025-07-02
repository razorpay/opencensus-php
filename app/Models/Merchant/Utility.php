<?php

namespace RZP\Models\Merchant;

use App;
use DOMXPath;
use ZipArchive;
use DOMDocument;
use Response;
use RZP\Constants\Country;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use RZP\Constants\InternationalStates;

class Utility
{

    public static function htmlToText($html)
    {
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $node  = $xpath->query('body')->item(0);

        return $node->textContent; // text
    }

    public static function downloadZip(array $files, string $zipFileName)
    {
        $zip = new ZipArchive;

        if ($zip->open($zipFileName, ZipArchive::CREATE) === true)
        {
            foreach ($files as $fileName => $canDelete)
            {
                $relativeNameInZipFile = basename($fileName);

                if (File::exists($fileName))
                {
                    $zip->addFile($fileName, $relativeNameInZipFile);
                }

            }

            $zip->close();

            foreach ($files as $fileName => $canDelete)
            {
                if ($canDelete === true and File::exists($fileName))
                {
                    File::delete($fileName);
                }
            }
        }

        return Response::download($zipFileName)->deleteFileAfterSend(true);

    }

    public static function getPolicyContentUpdateLiveDate()
    {
    //please don't change, it's live date of policy v2
        return  Carbon::create(2023, 12, 8,0,0,0);
    }

    public static function getRowMerchantCountry(): string
    {

        $merchant = app('basicauth')->getMerchant();

        return strtolower($merchant?->getCountry() ?? 'IN');
    }

    public static function getRowStateClass(?string $merchantCountry = null)
    {
        if (!$merchantCountry)
        {
            $merchantCountry = self::getRowMerchantCountry();
        }

        $stateClasses = InternationalStates::$rowCountryStateMap;

        return $stateClasses[$merchantCountry] ?? \RZP\Constants\IndianStates::class;
    }

    public static function addDummyIfsccode(): string
    {
        if (self::getRowMerchantCountry() === Country::IN)
        {
            return '';
        }

        return 'HDFC0000001';
    }

    public static function isValidMalaysianBIC($bic): bool
    {
        $pattern = '/^[A-Z]{4}MY[A-Z0-9]{2}([A-Z0-9]{3})?$/';

        return (preg_match($pattern, $bic) === 1) && (strlen($bic) === 8 || strlen($bic) === 11);
    }
}
