<?php

namespace RZP\Models\Gateway\File\Processor\Cardsettlement;

use Str;
use Mail;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Encryption\Type;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Status;
use RZP\Services\CardPaymentService;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    const FILE_METADATA            = [];
    const COMPRESSION_REQUIRED     = false;
    const EXTENSION                = FileStore\Format::TXT;
    const SHOULD_ENCRYPT           = false;
    const ENCRYPTION_TYPE          = Type::PGP_ENCRYPTION;

    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    protected $totalTransactions;


    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end   = $this->gatewayFile->getEnd();

        return [];
    }

    public function checkIfValidDataAvailable(PublicCollection $settlementPayments)
    {
        if ($settlementPayments->isEmpty() === true)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
    }

    public function generateData(PublicCollection $settlementPayments): array
    {
        $data = [];
        if ($settlementPayments->get('payments')->isNotEmpty() === true)
        {
            $data['payments'] = $settlementPayments->get('payments')->all();
        }

        if ($settlementPayments->get('refunds')->isNotEmpty() === true)
        {
            $data['refunds'] = $settlementPayments->get('refunds')->all();
        }

        return $data;
    }

    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteName();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata(static::FILE_METADATA);

            if (static::COMPRESSION_REQUIRED === true)
            {
                $creator->password($data['password'])
                        ->compress();
            }

            if (static::SHOULD_ENCRYPT === true)
            {
                $creator->encrypt(self::ENCRYPTION_TYPE, $this->getEncryptionParams());
            }

            $creator->save();

            $this->file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                   'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    /**
     * @param $data
     * @throws GatewayFileException
     */
    public function sendFile($data)
    {
        try
        {
            $this->sendSettlementPassword($data);

            $this->sendSettlementFile($data);

            $this->gatewayFile->setFileSentAt(Carbon::now()->getTimestamp());

            $this->gatewayFile->setStatus(Status::FILE_SENT);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id' => $this->gatewayFile->getId()
                ],
                $e
            );
        }

    }

    protected function getCardTokenBIN(string $cardToken)
    {
        return substr($cardToken, 0, 6);
    }

    protected function getCardToken(Card\Entity $card)
    {
        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardTokenId = $card->getVaultToken();

        $cardToken= (new Card\CardVault)->getCardNumber($cardTokenId, $card->toArray());

        return $cardToken;
    }

    protected function fetchAuthorizationDetails($data)
    {
        $paymentIds = array_key_exists('payments',$data) === true ? array_pluck($data['payments'], 'id'): [];

        $refundPaymentIds = array_key_exists('refunds',$data) === true ? array_pluck($data['refunds'], 'payment_id'): [];

        $ids = array_unique(array_merge($paymentIds, $refundPaymentIds));

        $ids = array_values($ids);

        if(count($ids) === 0)
        {
            $this->trace->info(TraceCode::CARD_SETTLEMENT_FILE_EMPTY_DATA, [
                'location' => 'fetchAuthorizationDetails',
                'authData' => 'No payment IDs, skipping CPS request',
            ]);
            return [];
        }

        $request = [
            'fields'      => [
                'gateway_reference_id2',

            ],
            'payment_ids' => $ids,
        ];

        $authData = $this->app['card.payments']->fetchAuthorizationData($request);

        if(count($ids) !== count($authData))
        {
            throw new GatewayFileException
            (
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'            => $this->gatewayFile->getId(),
                    'message'       => 'Discrepancy in authorization data fetch from CPS',
                    'Payment IDs'   => $ids,
                    'Payment count' => count($ids),
                    'CPS count'     => count($authData),
                ]
            );
        }

        return $authData;
    }

    protected function getAuthCode(Payment\Entity $payment)
    {
        $authCode = $payment->getReference2();

        if (empty($authCode) === true)
        {
            throw new Exception\LogicException(
                'Authorization Code cannot be empty.', null,
                [
                    'payment_id' => $payment->getPublicId(),
                    'auth_code'  => $authCode
                ]);
        }

        return $authCode;
    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }

    protected function getFileToWriteName()
    {
        return static::FILE_NAME;
    }

    protected function getFormattedDate($timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)
               ->format(static::DATE_FORMAT);
    }

}
