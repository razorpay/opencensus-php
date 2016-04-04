<?php

namespace Models\Emi\Banks\Axis;

use Carbon\Carbon;

use Services\TokenEx;
use Models\Emi\Service;

class EmiFile extends \Models\Emi\Banks\Base\EmiFile
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
        $fullpath = $this->getExcelFullFilePath();

        $data['file'] = $fullpath;
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
                $this->getCardNumber($emiPayment->card),
                $emiPayment->getAmount()/100,
                $date,
                $date,
                $this->getAuthCode($emiPayment),
                $emiPayment->merchant->getName(),
                $emiPayment->merchant->getCategory(),
                $emiTenure,
                'Razorpay',
                $emiPayment->getId(), // Can be empty, filling with out payment id
            );

        }

        return $data;
    }

    protected function getCardNumber($card)
    {
        $cardToken = $card->getCardToken();

        $app = \App::getFacadeRoot();

        $cardNumber = $app['card.tokenex']->detokenize($cardToken);

        return $cardNumber['Value'];
    }

    protected function getAuthCode($payment)
    {
        $gateway = ucfirst($payment->gateway);

        $entity = 'Gateway\\'.$gateway.'\\Entity';

        $repo = 'Gateway\\'.$gateway.'\\Repository';

        if (defined($repo))
        {
            $attributes = $repo->findByPaymentId($payment->id);

            return (new $entity)->build($attributes)->getAuthCode();
        }

        return '000000';
    }
}
