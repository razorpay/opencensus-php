<?php

namespace RZP\Models\Emi\Banks\Kotak;

use Gateway;
use RZP\Models\Emi;
use Services\TokenEx;
use RZP\Models\Emi\Banks\Base;

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
        $zipFile = $this->getZippedFile();

        $data['file'] = $zipFile;

        $data['body'] = 'Please forward the Kotak Emi file to kotak';

        $this->mail->queue('emails.message', $data, function ($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

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

            $gatewayEntity = $this->getGatewayEntity($emiPayment);

            $authCode = $gatewayEntity->getAuthCode();

            $data[] = array(
            'EMI ID'                     => $emiPayment->getId(),
            'Card Pan'                   => $this->getCardNumber($emiPayment->card),
            'Issuer'                     => 'Kotak',
            'Auth Code'                  => $authCode,
            'Tx Amount'                  => $emiPayment->getAmount()/ 100,
            'Tenure'                     => $emiPlan['duration'],
            'Manufacturer'               => '', // Non Mandatory
            'Merchant Name'              => $emiPayment->merchant->getName(),
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

    protected function getGatewayEntity($payment)
    {
        $gateway = ucfirst($payment->gateway);

        $entity = 'RZP\Gateway\\'.$gateway.'\\Entity';

        $repo = 'RZP\Gateway\\'.$gateway.'\\Repository';

        if (defined($repo))
        {
            $attributes = $repo->findByPaymentId($payment->id);

            return (new $entity)->build($attributes);
        }

        return new $entity;
    }
}
