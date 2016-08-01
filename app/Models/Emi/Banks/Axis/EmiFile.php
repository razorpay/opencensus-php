<?php

namespace RZP\Models\Emi\Banks\Axis;

use Carbon\Carbon;

use RZP\Services\TokenEx;
use RZP\Models\Card;
use RZP\Models\Emi\Service;
use RZP\Models\Emi\Banks\Base;
use RZP\Gateway\Base\Action;

class EmiFile extends Base\EmiFile
{
    protected static $fileToWriteName = 'Axis_Emi_File';

    protected static $headers = array(
        'Card Number',
        'Transaction Amount',
        'Transaction Date',
        'Settlement Date',
        'Authorisation Id',
        'Merchant Name',
        'MCC (Merchant Category Code)',
        'Tenure',
        'Source',
        'EMI ID',
    );

    public function generate($input)
    {
        $txt = $this->getEmiData($input);

        // Axis wants the file to be in CSV format, but named with a .txt extension
        $urlExcel = $this->writeToCsvFile($txt, $this->getFileToWriteNameWithoutExt(), $this->getTextFullFilePath());

        $this->sendAxisEmiFile();

        return $urlExcel;
    }

    protected function sendAxisEmiFile()
    {
        $this->fetchAndSendPassword();

        $fullPath = $this->getTextFullFilePath();

        $zipFile = $this->getZippedFile($fullPath);

        $data['file'] = $zipFile;
        $data['body'] = 'Please forward the Axis Emi file to axis';

        $this->mail->queue('emails.message', $data, function ($message) use ($data)
        {
            $emails = ['axiscards.emi@razorpay.com', 'settlements@razorpay.com'];

            $message->from('emifiles@razorpay.com', 'Axis Emi File');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('Axis Emi File for ' . $today);

            $message->to($emails);

            $message->attach($data['file']);
        });
    }

    protected function getEmiData($input)
    {
        $data = [];

        foreach ($input as $emiPayment)
        {
            $emiTenure = (new Service)->fetch($emiPayment->getEmiPlanId())['duration'];

            $data[] = array(
                'Card Number'                  => $this->getCardNumber($emiPayment->card),
                'Transaction Amount'           => $emiPayment->getAmount()/100,
                'Transaction Date'             => $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'Settlement Date'              => $this->formattedDateFromTimestamp($emiPayment->transaction->getSettledAt()),
                'Authorisation Id'             => $this->getAuthCode($emiPayment),
                'Merchant Name'                => 'Razorpay Payments',
                'MCC (Merchant Category Code)' => $emiPayment->merchant->getCategory(), // Non Mandatory,
                'Tenure'                       => $emiTenure,
                'Source'                       => 'Razorpay',
                'EMI ID'                       => $emiPayment->getId(), // Non Mandatory, filling with our payment id
            );
        }

        return $data;
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, 'Asia/Kolkata')->format('d-M-Y');
    }

    protected function sendEmiPassword()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');
        $data['body'] = 'Axis Emi File Password for ' . $today . " is " . $this->emiFilePassword;

        $this->mail->queue('emails.message', $data, function ($message) use ($data, $today)
        {
            $emails = ['axiscards.emi@razorpay.com'];

            $message->from('emifiles@razorpay.com', 'Axis Emi File Password');

            $message->subject('Axis Emi File Password for ' . $today);

            $message->to($emails);
        });
    }
}
