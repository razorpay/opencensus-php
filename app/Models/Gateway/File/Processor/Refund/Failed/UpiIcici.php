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
     const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
     const GATEWAY_CODE           = 'NPCI';
     const GATEWAY                = Payment\Gateway::UPI_ICICI;

}
