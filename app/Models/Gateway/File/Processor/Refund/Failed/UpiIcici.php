<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class UpiIcici extends Base
{
    use FileHandler;

     const GATEWAY                = Payment\Gateway::UPI_ICICI;
     const EXTENSION              = FileStore\Format::TXT;
     const FILE_NAME              = 'UPI_ICICI_failed_refunds';
     const FILE_TYPE              = FileStore\Type::ICICI_UPI_REFUND;


    protected function formatDataForFile(array $data)
    {
        $formattedData = [];
        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'],
                    Timezone::IST)
                    ->format('Y-d-m');
            $formattedData[] = [
                'Payee ID'      => $row['terminal']['gateway_merchant_id'],
                'Date'          => $date,
                'payment ID'    => $row['gateway']['payment_id'],
                'TXN Amount'    => $row['payment']['amount'] / 100,
                'Refund Amount' => $row['refund']['amount'] / 100,
            ];
        }
        return $this->getTextData($formattedData);
    }

}
