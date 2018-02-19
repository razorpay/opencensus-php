<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use BaconQrCode\Renderer;
use BaconQrCode\Writer;
use RZP\Models\VirtualAccount;
use RZP\Exception\LogicException;
use RZP\Models\FileStore\Utility;
use RZP\Services\Elfin\Service as Elfin;

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
     * Url shortener service
     * @var Elfin
     */
    protected $elfin;

    /**
     * Base qrcode url from which qr code link is generated.
     * @var string
     */
    protected $baseQrCodeUrl;

    // Qr code Extension
    const QR_CODE_EXTENSION = FileStore\Format::PNG;

    const SHORT_MODE_LIVE = 'l';
    const SHORT_MODE_TEST = 't';

    public function __construct(Merchant\Entity $merchant)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->elfin = $this->app['elfin'];

        $this->baseQrCodeUrl = $this->app['config']->get('app.url');
    }

    public function generate(array $input, VirtualAccount\Entity $virtualAccount): Entity
    {
        $this->createAndSetQrCode($input);

        $qrCode = $this->qrCode;

        $qrCode->merchant()->associate($this->merchant);

        $qrCode->source()->associate($virtualAccount);

        $qrCode->generateQrString();

        $this->setShortUrl();

        $this->repo->transaction(function() use ($qrCode)
        {
            $this->repo->saveOrFail($qrCode);

            $this->generateQrCodeFile();
        });

        return $this->qrCode;
    }

    protected function createAndSetQrCode(array $input)
    {
        $this->qrCode = (new Entity)->build($input)->generateId();
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

    /**
     * Qr Code Long URL
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function getQrCodeLink(): string
    {
        $qrCodePublicId = $this->qrCode->getPublicId();

        if ($this->mode === Mode::LIVE)
        {
            $shortMode = self::SHORT_MODE_LIVE;
        }
        else
        {
            $shortMode = self::SHORT_MODE_TEST;
        }

        $qrCodeLink = $this->baseQrCodeUrl . '/' . $shortMode . '/qrcode/' . $qrCodePublicId;

        return $qrCodeLink;
    }

    protected function generateQrCodeFile()
    {
        $localFilePath = $this->generateQrCodeImage();

        $ext = self::QR_CODE_EXTENSION;

        return (new FileStore\Creator)
                    ->localFilePath($localFilePath)
                    ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][0])
                    ->name($this->qrCode->getQrCodeFileName())
                    ->extension($ext)
                    ->entity($this->qrCode)
                    ->type(FileStore\Type::QR_CODE_IMAGE)
                    ->save();
    }

    protected function generateQrCodeImage()
    {
        $renderer = new Renderer\Image\Png;

        $renderer->setHeight(Constants::QR_CODE_HEIGHT);

        $renderer->setWidth(Constants::QR_CODE_WIDTH);

        $writer = new Writer($renderer);

        $localFilePath = $this->getLocalSaveDir() . '/' . $this->qrCode->getId() . '.' . self::QR_CODE_EXTENSION;

        $writer->writeFile($this->qrCode->getQrString(), $localFilePath);

        return $localFilePath;
    }

    protected function getLocalSaveDir(): string
    {
        $dirPath = storage_path('files/qrcodes');

        if (file_exists($dirPath) === false)
        {
            (new Utility)->callFileOperation('mkdir', [$dirPath, 0777, true]);
        }

        return $dirPath;
    }
}
