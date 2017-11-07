<?php

namespace RZP\Models\QrCode;

use Config;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use Endroid\QrCode\QrCode;
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

        $this->baseQrCodeUrl = $this->app['config']->get('app.url');
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

        $qrCodeLink = $this->baseQrCodeUrl . '/qrcode/' . $qrCodePublicId;

        return $qrCodeLink;
    }

    public function generate(array $input, $virtualAccount = null)
    {
        $qrCode = new Entity;

        $qrCode = $qrCode->build($input);

        $qrCode->generateId();

        $qrCode->merchant()->associate($this->merchant);

        $qrCode->source()->associate($virtualAccount);

        $qrCode = $qrCode->generateQrString();

        $this->qrCode = $qrCode;

        $this->setShortUrl();

        $this->repo->saveOrFail($qrCode);

        $qrCodeImage = $this->generateQrCodeFile();

        return $this->qrCode;
    }

    protected function setShortUrl()
    {
        $longUrl = $this->getQrCodeLink();

        $shortenedUrl = $this->elfin->shorten($longUrl);

        $this->trace->info(
            TraceCode::QR_CODE_URL,
            [
                'qr_code_id'     => $this->qrCode->getId(),
                'short_url'      => $shortenedUrl,
                'long_url'       => $longUrl,
            ]);

        $this->qrCode->setShortUrl($shortenedUrl);
    }

    protected function generateQrCodeFile()
    {
        $localFilePath = $this->generateQrCodeLocalFile();

        $this->generateQrCodeFileOnS3($localFilePath);
    }

    protected function getLocalSaveDir(): string
    {
        return storage_path('files/filestore');
    }

    protected function generateQrCodeLocalFile()
    {
        // Create a basic QR code
        $qrCodeImage = new QrCode($this->qrCode->getQrString());

        $qrCodeImage->setSize(300);

        $localFilePath = $this->getLocalSaveDir() . '/' . $this->qrCode->getId() . '.png';

        $qrCodeImage->writeFile($localFilePath);

        return $localFilePath;
    }

    protected function generateQrCodeFileOnS3(string $localFilePath)
    {
        $ext = pathinfo($localFilePath, PATHINFO_EXTENSION);

        return (new FileStore\Creator)
                    ->localFilePath($localFilePath)
                    ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][0])
                    ->name($this->qrCode->getQrCodeFileName())
                    ->extension($ext)
                    ->entity($this->qrCode)
                    ->merchant($this->merchant)
                    ->type(FileStore\Type::QR_CODE_IMAGES)
                    ->save();
    }
}
