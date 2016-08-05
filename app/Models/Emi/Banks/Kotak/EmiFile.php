<?php

namespace RZP\Models\Emi\Banks\Kotak;

use Gateway;
use RZP\Models\Emi;
use RZP\Services\TokenEx;
use RZP\Models\Emi\Banks\Base;
use RZP\Gateway\Base\Action;

use Carbon\Carbon;

class EmiFile extends Base\EmiFile
{

    protected static $fileToWriteName = 'Kotak_Emi_File';

    protected static $headers = array(
        'EMI ID',
        'Card Pan',
        'Issuer',
        'Auth Code',
        'Tx Amount',
        'Tenure',
        'Manufacturer',
        'Merchant Name',
        'Address1',
        'Acquirer',
        'MID',
        'TID',
        'Tx Time',
        'Settlement Time',
        'Interest Rate',
        'Discount / Cashback %',
        'Discount / Cashback Amount',
    );

    public function generate($input)
    {
        $txt = $this->getEmiData($input);

        $urlExcel = $this->writeToExcelFile($txt, $this->getFileToWriteNameWithoutExt());

        $this->sendKotakEmiFile();

        return $urlExcel;
    }

    protected function sendKotakEmiFile()
    {
        $this->fetchAndSendPassword();

        $fullPath = $this->getExcelFullFilePath();

        $zipFile = $this->getZippedFile($fullPath);

        $data['file'] = $zipFile;

        $data['body'] = 'Please process the attached EMI file';

        $this->mail->queue('emails.message', $data, function ($message) use ($data)
        {
            $emails = ['kotakcards.emi@razorpay.com', 'settlements@razorpay.com'];

            $message->from('emifiles@razorpay.com', 'Kotak Emi File');

            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $message->subject('Kotak Emi File for ' . $today);

            $message->to($emails);

            $message->attach($data['file']);
        });
    }

    protected function getEmiData($input)
    {
        $emiPayments = [];

        $data = [];

        foreach ($input as $emiPayment)
        {
            $date = Carbon::createFromTimestamp($emiPayment->getCaptureTimestamp(), 'Asia/Kolkata')->format('M d,Y h:i:s A');

            $emiPlan = (new Emi\Service)->fetch($emiPayment->getEmiPlanId());

            $emiPercent = $emiPlan['rate']/100;

            $authCode = $this->getAuthCode($emiPayment);

            $data[] = array(
            'EMI ID'                     => $emiPayment->getId(),
            'Card Pan'                   => $this->getCardNumber($emiPayment->card),
            'Issuer'                     => 'Kotak',
            'Auth Code'                  => $authCode,
            'Tx Amount'                  => $emiPayment->getAmount()/ 100,
            'Tenure'                     => $emiPlan['duration'],
            'Manufacturer'               => '', // Non Mandatory
            'Merchant Name'              => 'Razorpay Payments',
            'Address1'                   => '', // Non Mandatory
            'Acquirer'                   => '', // Non Mandatory
            'MID'                        => '', // Non Mandatory
            'TID'                        => '', // Non Mandatory
            'Tx Time'                    => $date,
            'Settlement Time'            => '', // Non Mandatory
            'Interest Rate'              => '', // Non Mandatory
            'Discount / Cashback %'      => '0.00%',
            'Discount / Cashback Amount' => '0'
            );
        }

        return $data;
    }

    protected function sendEmiPassword()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');
        $data['body'] = 'Kotak Emi File Password for ' . $today . " is " . $this->emiFilePassword;

        $this->mail->queue('emails.message', $data, function ($message) use ($data, $today)
        {
            $emails = ['kotakcards.emi@razorpay.com'];

            $message->from('emifiles@razorpay.com', 'Kotak Emi File Password');

            $message->subject('Kotak Emi File Password for ' . $today);

            $message->to($emails);
        });
    }
}
