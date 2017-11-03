<?php

namespace RZP\Models\QrCode;

use Config;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Services\Elfin\Service as Elfin;
use RZP\Exception\BadRequestValidationFailureException;

class Generator extends Base\Core
{
    /**
     * @var Entity
     */
    protected $qrCode;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * Elfin: Url shortener service
     */
    protected $elfin;

    /**
     * Base qrcode url from which qr code link is generated.
     * @var string
     */
    protected $baseQrCodeUrl;

    public function __construct(Merchant\Entity $merchant)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->elfin = $this->app['elfin'];

        $this->baseQrCodeUrl = 'abc.com';
    }

    /**
     * Qr Code Long URl
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function getQrCodeLink(): string
    {
        $qrCodePublicId = $this->qrCode->getPublicId();

        $invoiceLink = $this->baseQrCodeUrl . '/' . $qrCodePublicId;

        return $qrCodeLink;
    }

    public function generate(array $input)
    {
        $qrCode = new QrCode\Entity;

        $qrCode = $qrCode->build($input);

        $qrCode->generateId();

        $qrCode->merchant()->associate($this->merchant);

        $qrCode->source()->associate($virtualAccount);

        $qrCode = $qrCode->generateQrString();

        $qrCodeImage = $qrCode->generateQrCode();

        $this->qrCode = $qrCode;

        $this->repo->saveOrFail($qrCode);

        $this->qrCode = $qrCode;

        return $qrCode;
    }

    protected function setShortUrl()
    {
        $longUrl = $this->getQrCodeLink();

        $shortenedUrl = $this->elfin->shorten($longUrl);

        $this->trace->info(
            TraceCode::INVOICE_LINKS,
            [
                'invoice_id'     => $this->invoice->getId(),
                'invoice_status' => $this->invoice->getStatus(),
                'short_url'      => $shortenedUrl,
                'long_url'       => $longUrl,
            ]);

        $this->qrCode->setShortUrl($shortenedUrl);
    }

    protected function generateQrCodeFile()
    {
        $localFilePath = $this->generateQrCodeLocalFile();
    }

    protected function getLocalSaveDir(): string
    {
        return storage_path('files/filestore') . '/' . 'qrcode';
    }

    protected function generateQrCodeLocalFile()
    {
        // Create a basic QR code
        $qrCodeImage = new QrCode($this->qrCode->getQrString());

        $qrCodeImage->setSize(300);

        $localFilePath = $this->getLocalSaveDir() . '/' . $this->qrCode->getId() . 'png';

        $qrCodeImage->writeFile($localFilePath);

        return $localFilePath;
    }

    protected function getQrCodeFile($qrCode)
    {
         return (new FileStore\Creator)
                    ->localFilePath($localFilePath)
                    ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][5])
                    ->name($qrCode->getQrCodeFileName())
                    ->extension($ext)
                    ->entity($qrCode)
                    ->merchant($this->merchant)
                    ->type($type)
                    ->save();
    }
}
