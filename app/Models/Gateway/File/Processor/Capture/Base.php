<?php

namespace RZP\Models\Gateway\File\Processor\Capture;

use Str;
use Mail;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Emi as EmiMail;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    const FILE_METADATA            = [];
    const COMPRESSION_REQUIRED     = true;
    const EXTENSION                = FileStore\Format::TXT;

    public function fetchEntities(): PublicCollection
    {
        //Implement this if here if not implementing in individual processor or if generic
        return (new PublicCollection());
    }

    public function checkIfValidDataAvailable(PublicCollection $data)
    {
        //Todo: What to do with no data?
        // just returning true will create empty file (only header and trailer entries)
        return true;
    }

    public function generateData(PublicCollection $payments): array
    {
        $data['items'] = $payments->all();
        return $data;
    }

    public function createFile($data)
    {
        //Implement this if here if not implementing in individual processor or if generic
        return;
    }

    /**
     * @param $data
     * @throws GatewayFileException
     */
    public function sendFile($data)
    {
        try
        {
            //Todo: where to send?
//            $this->sendCapturePassword($data);

            $this->sendCaptureFile($data);

            $this->gatewayFile->setFileSentAt(Carbon::now()->getTimestamp());

            $this->gatewayFile->setStatus(Status::FILE_SENT);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                ['id' => $this->gatewayFile->getId()],
                $e
            );
        }
    }

    protected function getCardNumber(Card\Entity $card)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken);

        return $cardNumber;
    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }

    protected function getFileToWriteName()
    {
        return static::FILE_NAME;
    }
}
