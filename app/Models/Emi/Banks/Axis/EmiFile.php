<?php

namespace RZP\Models\Emi\Banks\Axis;

use Carbon\Carbon;

use RZP\Services\TokenEx;
use RZP\Models\Card;
use RZP\Models\Emi\Service;
use RZP\Models\Emi\Banks\Base;

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

        $urlExcel = $this->writeToExcelFile($txt, $this->getFileToWriteNameWithoutExt());

        $this->sendAxisEmiFile();

        return $urlExcel;
    }

    protected function sendAxisEmiFile()
    {
        $this->fetchAndSendPassword();

        $zipFile = $this->getZippedFile();

        $data['file'] = $zipFile;
        $data['body'] = 'Please forward the Axis Emi file to axis';

        $this->mail->queue('emails.message', $data, function ($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

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
            $date = Carbon::createFromTimestamp($emiPayment->getCaptureTimestamp(), 'Asia/Kolkata')->format('d-M-Y');

            $emiTenure = (new Service)->fetch($emiPayment->getEmiPlanId())['duration'];

            $data[] = array(
                'Card Number'                  => $this->getCardNumber($emiPayment->card),
                'Transaction Amount'           => $emiPayment->getAmount()/100,
                'Transaction Date'             => $date,
                'Settlement Date'              => $date,
                'Authorisation Id'             => $this->getAuthCode($emiPayment),
                'Merchant Name'                => $emiPayment->merchant->getName(),
                'MCC (Merchant Category Code)' => $emiPayment->merchant->getCategory(), // Non Mandatory,
                'Tenure'                       => $emiTenure,
                'Source'                       => 'Razorpay',
                'EMI ID'                       => $emiPayment->getId(), // Non Mandatory, filling with our payment id
            );
        }

        return $data;
    }

    protected function getAuthCode($payment)
    {
        $gateway = ucfirst($payment->gateway);

        $entity = 'RZP\Gateway\\'.$gateway.'\\Entity';

        $repo = 'RZP\Gateway\\'.$gateway.'\\Repository';

        if (defined($repo))
        {
            $attributes = $repo->findByPaymentId($payment->id);

            return (new $entity)->build($attributes)->getAuthCode();
        }

        return '000000';
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
