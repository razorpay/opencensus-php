<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class IrctcRefund extends Base
{
    const FILE_PREFIX = [
        '8ST00QgEPT14cE' => 'refundvalidation_RZP_BRDS_',
        '8YPFnW5UOM91H7' => 'refundvalidation_RZRPAY_BRDS_',
        '8byazTDARv4Io0' => 'refundvalidation_',
        'default'        => 'refundvalidation_',
    ];

    protected static $mailTag     = MailTags::BATCH_IRCTC_REFUNDS_FILE;

    protected static $sender      = Constants::IRCTC;

    protected static $subjectLine = "Razorpay | IRCTC Refunds File for %s";

    protected static $body        = 'Please upload IRCTC refund file on portal';

    protected function addAttachments()
    {
        $this->attach($this->outputFileLocalPath, ['as' => $this->getFileName()]);

        return $this;
    }

    /**
     * File name format/example: refundvalidation_RZRPAY__BRDS_20171212_V1
     *
     * @param string|null $ext
     *
     * @return string
     */
    protected function getFileName(): string
    {
        $time = Carbon::yesterday(Timezone::IST)->format('Ymd');

        $prefix = self::FILE_PREFIX[$this->merchant['id']] ?? self::FILE_PREFIX['default'];

        $name = $prefix . $time . '_V1';

        return $name . '.txt';
    }

}
