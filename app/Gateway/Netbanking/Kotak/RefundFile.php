<?php

namespace RZP\Gateway\Netbanking\Kotak;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Kotak_Netbanking_Refund';

    protected static $headers = [
        'S.No',
        'Mer.Id',
        'Date',
        'Mer.RefNo.',
        'Amount',
        'Bank.RefNo.'];

    public function generate($input)
    {
        list($txt, $totalAmount) = $this->getRefundData($input);

        $name = $this->getFileToWriteName();

        $filePath = $this->writeToTextFile($txt);

        $creator = new FileStore\Creator;

        $creator->extension(FileStore\Format::TXT)
                ->content($txt)
                ->name($name)
                ->store(FileStore\Store::S3)
                ->type(FileStore\Type::KOTAK_NETBANKING_REFUND)
                ->save();

        $file = $creator->get();

        $fileFullPath = $this->getFullFilePath($name);

        return [$totalAmount, $fileFullPath];
    }

    protected function getTextData($data, $prependLine = '')
    {
        $ignoreLastNewline = true;

        $txt = $this->generateText($data, '|', $ignoreLastNewline);

        $txt = $prependLine.$txt;

        return $txt;
    }

    protected function getRefundData($input)
    {
        // S.No in this file begins with 1
        $i = 1;

        $data = [];
        $totalAmount = 0;

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], 'Asia/Kolkata')->format('d-M-Y');

            $data[] = array(
                $i++,
                $row['gateway']['merchant_code'],
                $date,
                $row['gateway']['int_payment_id'],
                $row['refund']['amount'] / 100,
                $row['gateway']['bank_payment_id'],
            );

            $totalAmount = $totalAmount + $row['refund']['amount'] / 100;
        }

        $name = $this->getFileToWriteName();

        $i--;

        // First Line in the file is expected to be of the format
        // Format : FileName|ItemsCount|TotalAmount(Rs.)|CHECKSUM
        $initialLine = $name.'|'.$i.'|'.$totalAmount.'|CHECKSUM'."\r\n";

        $txt = $this->getTextData($data, $initialLine);

        return [$txt, $totalAmount];
    }
}
