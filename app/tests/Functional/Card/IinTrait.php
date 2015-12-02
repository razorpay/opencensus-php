<?php

namespace Tests\Functional\Card;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait IinTrait
{
    protected function generateIinFile()
    {
        $data = [
            ["MASTER BIN Report ","","","","",""],
            ["","","","","",""],
            ["BIN","BIN_LOW","BIN_HIGH","CARD_TYPE","CARD_BRAND","TYPE"],
            ["","","","","",""],
            ["510128","5101280000000000000","5101289999999999999","MCG","CLASSIC","FC"],
            ["510135","5101350000000000000","5101359999999999999","MCT","CLASSIC","DC"],
            ["511665","5116650000000000000","5116659999999999999","MRW","PREMIUM","FD"],
            ["511666","5116660000000000000","5116669999999999999","MRW","PREMIUM","DD"],
            ["513456","5134560000000000000","5134565999999999999","MRW","PREMIUM","DD"],
            ["513456","5134566000000000000","5116669999999999999","MRW","CLASSIC","DD"],
//            ["510128","5101285000000000000","5101289999999999999","MRW","CLASSIC","DC"],
        ];

        $request = array(
            'method' => 'post',
            'url' => '/iins/import/generate',
            'content' => ['data' => $data]);

        $file = $this->makeRequestAndGetContent($request);

        return $file;
    }

    protected function getUploadedIinFile()
    {
        $file = $this->generateIinFile();

        return $this->createUploadedFile($file);
    }

    protected function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = "application/vnd.ms-excel";
        $uploadedFile = new UploadedFile(
                                $file,
                                $file,
                                $mimeType,
                                filesize($file),
                                null,
                                true);

        return $uploadedFile;
    }
}
