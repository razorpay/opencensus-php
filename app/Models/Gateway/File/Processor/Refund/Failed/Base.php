<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Mail;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\Refund;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class Base extends Refund\Base
{
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();
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
}
