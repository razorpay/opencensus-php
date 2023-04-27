<?php


namespace RZP\Models\Gateway\File\Processor\Emi;

use Mail;
use App;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Card as Card;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Trace\TraceCode;


class Hsbc extends Base
{
    const BANK_CODE             = IFSC::HSBC;
    const EXTENSION             = FileStore\Format::XLSX;
    const FILE_TYPE             = FileStore\Type::HSBC_EMI_FILE;
    const FILE_NAME             = 'HSBC_EMI';
    const DATE_FORMAT           = 'd/m/Y';
    const APPROVAL_CODE_LENGTH  = 6;
    const DURATION              = 'months';

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        $rrn = $this->getRrnNumber($data['items']);

        foreach ($data['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;
            $merchant = $emiPayment->merchant;
            $terminal = $emiPayment->terminal;

            $cardNumber = $emiPayment->card->getLast4();

            $formattedData[] = [
                'Last 4 digits of Card Number'      =>  $cardNumber,
                'Amount'                            =>  $emiPayment->getAmount()/100,
                'MID'                               =>  $terminal->getGatewayMerchantId(),
                'TID'                               =>  $terminal->getId(),
                'Approval Code'                     =>  str_pad($this->getAuthCode($emiPayment), self::APPROVAL_CODE_LENGTH, '0', STR_PAD_LEFT),
                'Date'                              =>  $this->formattedDateFromTimestamp($emiPayment->getCreatedAt()),
                'EMI Tenure'                        =>  $emiPlan->getDuration().' '.self::DURATION,
                'Merchant Name'                     =>  $merchant->getName(),
                'Interest Rate'                     =>  $emiPlan->getRate()/100,
                'RRN Number'                        =>  $rrn[$emiPayment->getId()]['rrn'] ?? '',
                'Processing fee'                    =>  '0',
                'Merchant Cash Bank'                =>  '0',
                'Cashback Amount'                   =>  '0',
                'Aggregator Txn ID'                 =>  '',
                'ARN'                               =>  '',
                'Time Stamp'                        =>   Carbon::now()->getTimestamp(),
                'File Name'                         =>  $this->makeFileName($emiPayment->getCreatedAt()),
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => static::BANK_CODE,
                ]
            );
        }

        return $formattedData;
    }

    protected function getRrnNumber($data)
    {
        $CPS_PARAMS = [
             \RZP\Reconciliator\Base\Constants::RRN
        ];

        $paymentIds = array();

        foreach ($data as $payment)
        {
            array_push($paymentIds, $payment->id);
        }

        $request = [
            'fields'        => $CPS_PARAMS,
            'payment_ids'   => $paymentIds,
        ];

        $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

        return $response;
    }

    private function makeFileName($timestamp)
    {
        $date = Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('dmY');

        return self::FILE_NAME . '_' . $date . '_' . 'Razorpay';
    }

    private function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format(self::DATE_FORMAT);
    }

    public function checkIfValidDataAvailable(PublicCollection $emiPayments)
    {
        if ($emiPayments->isEmpty() === true)
        {
            $target = $this->gatewayFile->getTarget();

            $recipients = $this->gatewayFile->getRecipients();

            $noPasswordMail = new EmiMail\NoTransaction(
                ucfirst($target),
                $recipients
            );

            Mail::queue($noPasswordMail);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
    }

    protected function getCardNumber($card,$gateway=null)
    {
        if ($card->globalCard !== null) {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken,$card->toArray(),$gateway);

        return $cardNumber;
    }

    public function generateData(PublicCollection $emiPayments): array
    {
        $data['items'] = $emiPayments->all();

        if($this->mode === Mode::TEST)
        {
            $monthYear = Carbon::now(Timezone::IST)->format('mY');

            $data['password'] = "razorpay" . $monthYear;
        }
        else {
            $data['password'] = $this->generateEmiFilePassword();
        }

        return $data;
    }
}
