<?php

namespace RZP\Tests\Functional\Card;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait IinTrait
{
    protected function generateExcelData()
    {
        $data = [
            ["MASTER BIN Report ","","","","",""],
            ["","","","","",""],
            ["BIN","BIN_LOW","BIN_HIGH","CARD_TYPE","CARD_BRAND","TYPE","MESSAGE_TYPE"],
            ["","","","","",""],
            ["510128","5101280000000000000","5101289999999999999","MCG","CLASSIC","FC","DMS"],
            ["510135","5101350000000000000","5101359999999999999","MCT","CLASSIC","DC"],
            ["511665","5116650000000000000","5116659999999999999","MRW","PREMIUM","FD"],
            ["511666","5116660000000000000","5116664999999999999","MRW","PREMIUM","DD"],
            ["511666","5116665000000000000","5116669999999999999","MRW","PREMIUM","DD"],
            ["513456","5134560000000000000","5134565999999999999","MRW","PREMIUM","DD"],
            ["513456","5134566000000000000","5116669999999999999","MRW","CLASSIC","DD"],
            ["549752","5497520000000000000","5497529999999999999","MCG","STANDARD","DC"],
            ["497522","4975220000000000000","4975229999999999999","MGC","CLASSIC","DC"],
            ["559300","5593000000000000000","5593009999999999999","MCW","PREMIUM","DC"],
        ];

        return $data;
    }

    protected function generateExcelDataWithIssuer()
    {
        $data = [
            ["MASTER BIN Report ","","","","",""],
            ["","","","","",""],
            ["BIN","BIN_LOW","BIN_HIGH","CARD_TYPE","CARD_BRAND","TYPE","ISSUER"],
            ["","","","","",""],
            ["510128","5101280000000000000","5101289999999999999","MCG","CLASSIC","FC","SBIN"],
            ["510135","5101350000000000000","5101359999999999999","MCT","CLASSIC","DC","SBIN"],
            ["511665","5116650000000000000","5116659999999999999","MRW","PREMIUM","FD","SBIN"],
            ["511666","5116660000000000000","5116664999999999999","MRW","PREMIUM","DD","SBIN"],
            ["511666","5116665000000000000","5116669999999999999","MRW","PREMIUM","DD","SBIN"],
            ["513456","5134560000000000000","5134565999999999999","MRW","PREMIUM","DD","SBIN"],
            ["513456","5134566000000000000","5116669999999999999","MRW","CLASSIC","DD","SBIN"],
            ["549752","5497520000000000000","5497529999999999999","MCG","STANDARD","DC","SBIN"],
            ["497522","4975220000000000000","4975229999999999999","MGC","CLASSIC","DC","SBIN"],
            ["559300","5593000000000000000","5593009999999999999","MCW","PREMIUM","DC","SBIN"],
        ];

        return $data;
    }

    protected function generateExcelDataWithMessageType()
    {
        $data = [
            ["MASTER BIN Report ","","","","",""],
            ["","","","","",""],
            ["BIN","BIN_LOW","BIN_HIGH","CARD_TYPE","CARD_BRAND","TYPE","MESSAGE_TYPE"],
            ["","","","","",""],
            ["510128","5101280000000000000","5101289999999999999","MCG","CLASSIC","FC","SMS"],
            ["510135","5101350000000000000","5101359999999999999","MCT","CLASSIC","DC","SMS"],
            ["511665","5116650000000000000","5116659999999999999","MRW","PREMIUM","FD","SMS"],
            ["511666","5116660000000000000","5116664999999999999","MRW","PREMIUM","DD","SMS"],
            ["511666","5116665000000000000","5116669999999999999","MRW","PREMIUM","DD","SMS"],
            ["513456","5134560000000000000","5134565999999999999","MRW","PREMIUM","DD","SMS"],
            ["513456","5134566000000000000","5116669999999999999","MRW","CLASSIC","DD","SMS"],
            ["549752","5497520000000000000","5497529999999999999","MCG","STANDARD","DC","SMS"],
            ["497522","4975220000000000000","4975229999999999999","MGC","CLASSIC","DC","SMS"],
            ["559300","5593000000000000000","5593009999999999999","MCW","PREMIUM","DC","DMS"],
        ];

        return $data;
    }

    protected function generateIinFile($data)
    {
        $request = array(
            'method' => 'post',
            'url' => '/iins/import/generate',
            'content' => ['data' => $data]);

        $file = $this->makeRequestAndGetContent($request);

        return $file;
    }

    protected function getUploadedIinFile($withIssuer = false, $withMessageType = false)
    {
        if ($withIssuer === true)
        {
            $data = $this->generateExcelDataWithIssuer();
        }
        else if ($withMessageType === true)
        {
            $data = $this->generateExcelDataWithMessageType();
        }
        else
        {
            $data = $this->generateExcelData();
        }

        $file = $this->generateIinFile($data);

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
                                null,
                                true);

        return $uploadedFile;
    }
}
