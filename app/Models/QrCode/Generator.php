<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use Endroid\QrCode\QrCode;
use RZP\Models\VirtualAccount;
use RZP\Exception\LogicException;
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

        $this->repo->saveOrFail($qrCode);

        $this->generateQrCodeFile();

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

        $ext = FileStore\Format::PNG;

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

    protected function generateQrCodeImage()
    {
        $qrCodeImage = new QrCode($this->qrCode->getQrString());

        $qrCodeImage->setSize(Constants::QR_CODE_SIZE);

        $localFilePath = $this->getLocalSaveDir() . '/' . $this->qrCode->getId() . '.png';

        $qrCodeImage->writeFile($localFilePath);

        return $localFilePath;
    }

    protected function getLocalSaveDir(): string
    {
        $dir_to_save = storage_path('files/qrcode');

        if (!is_dir($dir_to_save)) {
            mkdir($dir_to_save);
        }

        return storage_path('files/qrcode');
    }
}
