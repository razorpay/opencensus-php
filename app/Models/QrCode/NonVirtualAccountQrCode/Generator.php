<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Models\Vpa;
use RZP\Models\QrCode;
use RZP\Models\Settings;
use RZP\Models\Payment;
use BaconQrCode\Writer;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use BaconQrCode\Renderer;
use RZP\Gateway\Upi\Base;
use RZP\Models\BankAccount;
use RZP\Models\BharatQr\Tags;
use RZP\Models\QrCode\Entity;
use RZP\Models\Payment\Gateway;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\QrCode\Constants as Constants;
use RZP\Models\BharatQr\Constants as BQRConstants;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVAQrEntity;

class Generator extends QrCode\Generator
{
    const BILLING_LABEL_LENGTH = 10;
    const MAX_VPA_LENGTH       = 20;
    const AROBASE              = '@';
    const QR                   = 'qr';
    const VPA                  = 'vpa';
    const TR_PREFIX            = 'RZP';
    const VPA_NUM_CHAR_SPACE   = '0123456789';
    const GATEWAY              = Gateway::UPI_ICICI;

    protected function getVpaSetting($gateway)
    {
        try
        {
            $vpaSetting = $this->fetchVpaSetting($gateway);

            if (empty($vpaSetting[$gateway]) === true)
            {
                $terminal = (new TerminalProcessor())->getTerminalForUpiTransfer(null, $gateway);

                $vpaSetting = $this->generateVpaForQrCode($terminal, $vpaSetting);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            throw $ex;
        }

        return $vpaSetting[$gateway];
    }

    /**
     * Fetches Bharat QR UPI identifiers for merchant
     * @param Entity $qrCode
     *
     * @return array
     * @throws \Exception
     */
    protected function getUpiIdentifier(Entity $qrCode): array
    {
        // If UPI isn't enabled at all, we skip addition of UPI identifiers
        if ((new Merchant\Methods\Service())->isMethodEnabledForMerchant(Payment\Method::UPI, $qrCode->merchant) === false)
        {
            return [];
        }

        if ($qrCode->getId() === NonVAQrEntity::SHARED_ID)
        {
            return ['vpa' => QrCode\Constants::DUMMY_QR_CODE_VPA];
        }

        $variant = $this->app->razorx->getTreatment($qrCode->merchant->getId(), Merchant\RazorxTreatment::QR_CODE_DYNAMIC_VPA, $this->mode);

        if ($variant === 'on')
        {
            $identifier[self::VPA] = $this->generateVpaForQr($qrCode);
        }
        else
        {
            $identifier[self::VPA] = $this->getVpaSetting(self::GATEWAY);
        }

        $this->trace->info(TraceCode::BHARAT_QR_UPI_IDENTIFIERS,
                           [
                               'qr_code'    => $qrCode->toArrayPublic(),
                               'identifier' => $identifier,
                           ]);

        return $identifier;
    }

    private function generateVpaForQr($qrCode)
    {
        return (new Vpa\Generator($this->merchant, []))->generate($qrCode)->getAddress();
    }

    protected function getTransactionReferenceTlv($qrCode)
    {
        return Tags::UPI_VPA_REFERENCE_TR . $this->getLengthAndValue(BQRConstants::UPI_PREFIX . $qrCode->getId() .
                                                                     QrCode\Constants::QR_CODE_V2_TR_SUFFIX);
    }

    /**
     * Generates UPI QR Code intent URL
     *
     * @param Entity $qrCode
     *
     * @return mixed|string
     * @throws \Exception
     */
    protected function getUpiQrCode(Entity $qrCode)
    {
        $this->trace->info(TraceCode::GENERATE_UPI_QR_CODE, [
            'id' => $qrCode->getId()
        ]);

        $variant = $this->app->razorx->getTreatment($qrCode->merchant->getId(), Merchant\RazorxTreatment::QR_CODE_DYNAMIC_VPA, $this->mode);

        if ($variant === 'on')
        {
            $vpa = $this->generateVpaForQr($qrCode);
        }
        else
        {
            $vpa = $this->getVpaSetting(self::GATEWAY);
        }

        return $this->generateUpiQrIntentUrl($vpa, $qrCode);
    }

    private function generateUpiQrIntentUrl($vpa, $qrCode)
    {
        $content = [
            Base\IntentParams::VERSION       => QrCode\Constants::QR_V2_VERSION,
            Base\IntentParams::MODE          => $this->getQrCodeMode($qrCode),
            Base\IntentParams::PAYEE_ADDRESS => $vpa,
            Base\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $this->merchant->getFilteredDba()),
            Base\IntentParams::TXN_REF_ID    => self::TR_PREFIX . $qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX,
            Base\IntentParams::TXN_NOTE      => 'Payment to ' . $this->merchant->getFilteredDba(),
            Base\IntentParams::TXN_CURRENCY  => 'INR',
            Base\IntentParams::MCC           => $this->merchant->getCategory(),
            Base\IntentParams::QR_MEDIUM     => QrCode\Constants::QR_V2_QR_MEDIUM,
        ];

        if ($qrCode->hasFixedAmount())
        {
            $content[Base\IntentParams::TXN_AMOUNT] = $qrCode->getAmount() / 100;
        }

        $content = array_merge($content, InvoiceDetails::getTaxDetails($qrCode));

        return 'upi://pay?' . str_replace(' ', '', urldecode(http_build_query($content)));
    }

    /**
     * Response is json storing VPA for gateways
     * {
     *      "upi_icici": "rpy.qrmoremegatore123@icici",
     *      "upi_mindgate": "rpy.qrmoremegatore123@hdfcbank"
     * }
     *
     * @param $gateway
     *
     * @return mixed
     * @throws \Exception
     */
    private function fetchVpaSetting($gateway)
    {
        try
        {
            $response = (new Settings\Service())->get(Settings\Module::QR_CODE, self::VPA);

            $response = json_decode($response['settings'], true);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::QR_CODE_VPA_SETTING_FETCH_FAILED, [
                'gateway' => $gateway
            ]);

            throw $ex;
        }

        return $response;
    }

    private function generateVpaForQrCode(Terminal\Entity $terminal, $vpaSetting)
    {
        $this->trace->info(TraceCode::QR_CODE_VPA_GENERATION_REQUEST);

        try
        {
            $vpa = $this->generateVpaSettingValue($terminal);

            $vpaSetting[$terminal->getGateway()] = $vpa;

            $setting[self::VPA] = json_encode($vpaSetting);

            (new Settings\Service())->upsert(Settings\Module::QR_CODE, $setting);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::QR_CODE_VPA_GENERATION_FAILED, [
                'terminal_id' => $terminal->getId()
            ]);

            throw $ex;
        }

        $this->trace->info(TraceCode::QR_CODE_VPA_GENERATION_SUCCESS, $setting);

        return $vpaSetting;
    }

    private function generateVpaSettingValue(Terminal\Entity $terminal)
    {
        $merchantIdentifier = preg_replace('/[^A-Za-z0-9]/', '', $this->merchant->getBillingLabel());

        $merchantIdentifier = self::QR . substr($merchantIdentifier, 0, self::BILLING_LABEL_LENGTH);

        $descriptor = $this->generateDescriptor(self::MAX_VPA_LENGTH - strlen($merchantIdentifier));

        $prefix = $terminal->getVirtualUpiRoot() . $merchantIdentifier;

        $handle = $terminal->getVirtualUpiHandle();

        return strtolower($prefix . $descriptor . self::AROBASE . $handle);
    }

    protected function generateDescriptor(int $desiredLength): string
    {
        $pad = '';

        $charSpace = $this->getCharSpace();

        while (strlen($pad) < $desiredLength)
        {
            $pad .= $charSpace[array_rand($charSpace)];
        }

        return $pad;
    }

    protected function getCharSpace(): array
    {
        return str_split(self::VPA_NUM_CHAR_SPACE);
    }

    public function generateUpiQrCodeImage($qrCode)
    {
        $localFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '.' . Constants::QR_CODE_EXTENSION;

        $qrCodeImage = $this->getQrCodeStringAndGenerateImage($qrCode);

        $displayDetails = $this->getMerchantDisplayDetails();

        $this->setMerchantLogoInQrImage($displayDetails['logo'], $qrCodeImage);

        $logoImage = imagecreatefrompng(public_path() . '/img/new_upi_qr.png');

        imageAlphaBlending($logoImage, true);

        imageSaveAlpha($logoImage, true);

        $this->imagecopymerge_alpha($logoImage, $qrCodeImage,
                                    Constants::QR_V2_UPI_QR_DEST_X, Constants::QR_V2_UPI_QR_DEST_Y,
                                    Constants::SORCE_X, Constants::SORCE_Y,
                                    Constants::QR_V2_UPI_QR_CODE_WIDTH, Constants::QR_V2_UPI_QR_CODE_HEIGHT,
                                    Constants::OPACITY);

        $color = imagecolorallocate($logoImage, 4, 9, 63);

        $ypos = QrCode\Constants::QR_V2_UPI_QR_NAME_YPOS;

        $this->alignCentre($logoImage, $displayDetails['name'], $color, 'Mulish-ExtraBold.ttf', $ypos, 40, 20);

        $this->alignCentre($logoImage, $qrCode->getDescription(), $color, 'Mulish-SemiBold.ttf', $ypos, 25, 40);

        imagepng($logoImage, $localFilePath);

        imagedestroy($logoImage);

        imagedestroy($qrCodeImage);

        return $localFilePath;
    }

    public function generateBharatQrCodeImage($qrCode)
    {
        $renderer = new Renderer\Image\Png;

        $renderer->setMargin(Constants::MARGIN);

        $renderer->setHeight(Constants::QR_CODE_HEIGHT);

        $renderer->setWidth(Constants::QR_CODE_WIDTH);

        $writer = new Writer($renderer);

        $localFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '.' . Constants::QR_CODE_EXTENSION;

        $qrCodeString = $writer->writeString($qrCode->getQrString());

        $logoImage = imagecreatefromjpeg(public_path() . '/img/qr.jpg');

        $qrCodeImage = imagecreatefromstring($qrCodeString);

        if ($qrCode->getId() !== NonVAQrEntity::SHARED_ID)
        {
            $color = imagecolorallocate($logoImage, 4, 9, 63);

            $ypos = Constants::QR_V2_BHARAT_QR_NAME_YPOS;

            $displayDetails = $this->getMerchantDisplayDetails();

            $this->alignCentre($qrCodeImage, $displayDetails['name'], $color, 'Mulish-ExtraBold.ttf', $ypos, 10, 30);

            $this->alignCentre($qrCodeImage, $qrCode->getDescription(), $color, 'Mulish-SemiBold.ttf', $ypos, 8, 50);
        }
        imagecopymerge($logoImage, $qrCodeImage,
                       Constants::QR_DEST_X, Constants::QR_DEST_Y,
                       Constants::SORCE_X, Constants::SORCE_Y,
                       Constants::QR_CODE_WIDTH, Constants::QR_CODE_HEIGHT,
                       Constants::OPACITY);

        imagejpeg($logoImage, $localFilePath);

        imagedestroy($logoImage);

        imagedestroy($qrCodeImage);

        return $localFilePath;
    }

    private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        $cut = imagecreatetruecolor($src_w, $src_h);

        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    }

    /**
     * @param $qrCode
     *
     * @return string
     */
    protected function getQrCodeStringAndGenerateImage($qrCode)
    {
        $renderer = new Renderer\Image\Png;

        $renderer->setMargin(Constants::MARGIN);

        $renderer->setHeight(Constants::QR_V2_UPI_QR_CODE_WIDTH);

        $renderer->setWidth(Constants::QR_V2_UPI_QR_CODE_HEIGHT);

        $renderer->setForegroundColor(new Renderer\Color\Rgb(4, 9, 63));

        $writer = new Writer($renderer);

        $qrCodeString = $writer->writeString($qrCode->getQrString());

        return imagecreatefromstring($qrCodeString);
    }

    private function alignCentre($logoImage, $text, $color, $font, & $ypos, $size, $width)
    {
        if (empty($text) === true)
        {
            return;
        }

        $font_file = public_path() . '/fonts/' . $font;

        $textWrap = wordwrap($text, $width, '\n', false);

        $lines = explode('\n', $textWrap);

        foreach ($lines as $line)
        {
            $type_space = imagettfbbox($size, 0, $font_file, $line);
            $line_width = abs($type_space[4] - $type_space[0]);
            $line_height = abs($type_space[5] - $type_space[1]) + 10;

            $centre = imagesx($logoImage)/ 2;

            $xpos = $centre - $line_width/2;

            imagettftext($logoImage, $size, 0, $xpos, $ypos, $color, $font_file, $line);

            $ypos += $line_height;
        }
    }

    private function getMerchantDisplayDetails()
    {
        $partners = (new Merchant\Core())->fetchAffiliatedPartners($this->merchant->getId());

        //submerchant can belong to only one aggregator or fully managed at a time
        $partner = $partners->filter(function(Merchant\Entity $partner)
        {
            return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
        })->first();

        if ($partner === null)
        {
            $partner = $this->merchant;
        }

        return [
            'logo' => $partner->getFullLogoUrlWithSize(),
            'name' => $partner->getName()
        ];
    }

    protected function setMerchantLogoInQrImage($logo, & $qrImage)
    {
        if ($logo === null)
        {
            return;
        }

        try
        {
            $merchantLogo = imagecreatefromstring(file_get_contents($logo));
            $logo_width   = imagesx($merchantLogo);
            $logo_height  = imagesy($merchantLogo);

            $qr_width  = imagesx($qrImage);
            $qr_height = imagesy($qrImage);
            //create new image
            $finalImage = imagecreatetruecolor($qr_width, $qr_height);
            imagealphablending($finalImage, true);
            $transparent = imagecolorallocatealpha($finalImage, 4, 9, 63, 127);
            imagefill($finalImage, 0, 0, $transparent);

            imagecopy($finalImage, $qrImage, 0, 0, 0, 0, $qr_width, $qr_height);

            $mergeRatio           = round($logo_width / $logo_height, 2);
            $postMergeImageWidth  = intval($qr_width * .2);
            $postMergeImageHeight = intval($postMergeImageWidth / $mergeRatio);

            $centerX = intval(($qr_width / 2) - ($postMergeImageWidth / 2));
            $centerY = intval(($qr_height / 2) - ($postMergeImageHeight / 2));

            imagecopyresampled($finalImage, $merchantLogo, $centerX, $centerY, 0, 0,
                               $postMergeImageWidth, $postMergeImageHeight,
                               $logo_width, $logo_height);

            $qrImage = $finalImage;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            return;
        }
    }

    public function getMerchantAccountIdentifier($qrCode)
    {
        if ((new Merchant\Methods\Service())->isMethodEnabledForMerchant(Payment\Method::BANK_TRANSFER, $qrCode->merchant) === false)
        {
            return parent::getMerchantAccountIdentifier($qrCode);
        }

        $variant = $this->app->razorx->getTreatment($qrCode->merchant->getId(), Merchant\RazorxTreatment::QR_CODE_BANK_TRANSFER, $this->mode);

        if ($variant !== 'on')
        {
            return parent::getMerchantAccountIdentifier($qrCode);
        }

        $bankAccount = (new BankAccount\Generator($qrCode->merchant, ['name' => $qrCode->merchant->getName()]))->generate($qrCode);

        $value = $bankAccount->getIfscCode() . $bankAccount->getAccountNumber();

        return Tags::MERCHANT_ACCOUNT . $this->getLengthAndValue($value);
    }

    protected function getBharatQrAdditionalDetailTlv(Entity $qrCode, array $merchantIdentifiers)
    {
        $idTlv = Tags::ADDITIONAL_DETAIL_ID . $this->getLengthAndValue($qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX);

        if (isset($merchantIdentifiers['rupay_tid']) === true)
        {
            $terminalIdTlv = Tags::TERMINAL_ID . $this->getLengthAndValue($merchantIdentifiers['rupay_tid']);

            $idTlv .= $terminalIdTlv;
        }

        $additionalDetailsString = $idTlv;

        return Tags::ADDITIONAL_DETAIL . strlen($additionalDetailsString) . $additionalDetailsString;
    }

    private function getQrCodeMode($qrCode)
    {
        if ($qrCode->getUsageType() === UsageType::MULTIPLE_USE)
        {
            return QrCode\Constants::QR_V2_MODE_STATIC;
        }
        else
        {
            return QrCode\Constants::QR_V2_MODE_DYNAMIC;
        }
    }
}
