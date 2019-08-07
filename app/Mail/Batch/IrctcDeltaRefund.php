<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;

class IrctcDeltaRefund extends Base
{
    const FILE_PREFIX = [
        '8ST00QgEPT14cE' => 'ngetdeltaconfirm_RZP_BRDS_',
        '8YPFnW5UOM91H7' => 'ngetdeltaconfirm_RZRPAY_BRDS_',
        'default'        => 'ngetdeltaconfirm_',
    ];

    protected static $mailTag     = MailTags::BATCH_IRCTC_DELTA_REFUNDS_FILE;

    protected static $sender      = Constants::IRCTC;

    protected static $subjectLine = 'Razorpay | IRCTC Delta Refunds File for %s';

    protected static $body        = 'Please upload IRCTC delta refund file on portal';

    protected function addAttachments()
    {
        $this->attach($this->outputFileLocalPath, ['as' => $this->getFileName()]);

        return $this;
    }

    /**
     * File name format/example: ngetdeltaconfirmation_RZP_BRDS_20171212_V1
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
