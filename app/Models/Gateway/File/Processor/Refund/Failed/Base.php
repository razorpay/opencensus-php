<?php

namespace RZP\Models\Gateway\File\Processor\Refund\Failed;

use Mail;

use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\Refund;
use RZP\Mail\Gateway\FailedRefund\Base as FailedRefundFileMail;

class Base extends Refund\Base
{

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        // For Card gateways only refunds that that was processed after
        //  6 months from payment creation needs to be manually processed
        if (Gateway::isMethodSupported(Method::CARD,  static::GATEWAY))
        {
            $refunds = $this->repo->refund->fetchFailedCardRefundsToProcessedManually(
                $begin,
                $end,
                static::GATEWAY
                );
        }
        else
        {
            $refunds = $this->repo->refund->fetchFailedRefundsForGatewayBetweenTimestamps(
                $begin,
                $end,
                static::GATEWAY
                );
        }

        return $refunds;
    }

    public function sendFile($data)
    {
        try
        {
            $recipients = $this->gatewayFile->getRecipients();

            $mailData = $this->formatDataForMail($data);

            $refundFileMail = new FailedRefundFileMail($mailData, static::GATEWAY, $recipients);

            Mail::queue($refundFileMail);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function formatDataForMail(array $data)
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

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
