<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Mail;

use RZP\Models\FileStore;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\Refund;
use RZP\Mail\Gateway\FailedRefund\Base as FailedRefundFileMail;

class Base extends Refund\Base
{
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        /**
         * For Card gateways only refunds that are failed before
         *  6 months are processed via file
        **/

        if (Gateway::isMethodSupported(Method::CARD,  static::GATEWAY))
        {
            $begin = $this->gatewayFile->getBegin() - 15780000;

            $end = $this->gatewayFile->getEnd() - 15780000;
        }

        $refunds = $this->repo->refund->fetchFailedRefundsForGatewayBetweenTimestamps(
                    $begin,
                    $end,
                    static::GATEWAY
                );
        return $refunds;
    }

    protected function formatDataForMail()
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name' => $file->getLocation(),
            'signed_url' => $signedUrl
        ];

        return $mailData;
    }

    protected function refundMail($mailData, $recipients)
    {
        $refundFileMail = new FailedRefundFileMail($mailData, static::GATEWAY, $recipients);

        return $refundFileMail;
    }
}
