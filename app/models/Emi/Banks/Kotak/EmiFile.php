<?php

namespace Models\Emi\Banks\Kotak;

use Gateway;
use Models\Emi;
use Services\TokenEx;

use Carbon\Carbon;

class EmiFile extends \Models\Emi\Banks\Base\EmiFile
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
        $fullpath = $this->getExcelFullFilePath();

        $data['file'] = $fullpath;

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
                $emiPayment->getId(),
                $this->getCardNumber($emiPayment->card),
                'Kotak',
                $authCode,
                $emiPayment->getAmount()/ 100,
                $emiPlan['duration'],
                'Samsung',
                $emiPayment->merchant->getName(),
                'NA',
                $emiPayment->terminal->gateway,
                $emiPayment->terminal->gateway_merchant_id,
                $gatewayEntity->getTransactionId(),
                $date,
                $date,
                $emiPercent.'%',
                '0.00%',
                '0'
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

    protected function getGatewayEntity($payment)
    {
        $gateway = ucfirst($payment->gateway);

        $entity = 'Gateway\\'.$gateway.'\\Entity';

        $repo = 'Gateway\\'.$gateway.'\\Repository';

        if (defined($repo))
        {
            $attributes = $repo->findByPaymentId($payment->id);

            return (new $entity)->build($attributes);
        }

        return new $entity;
    }
}
