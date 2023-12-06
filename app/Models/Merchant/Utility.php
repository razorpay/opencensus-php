<?php

namespace RZP\Models\Merchant;


use DOMXPath;
use ZipArchive;
use DOMDocument;
use Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

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
}
