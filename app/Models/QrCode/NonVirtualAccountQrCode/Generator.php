<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Vpa;
use RZP\Models\QrCode;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Feature;
use BaconQrCode\Writer;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use BaconQrCode\Renderer;
use RZP\Gateway\Upi\Base;
use RZP\Models\BankAccount;
use RZP\Models\BharatQr\Tags;
use RZP\Models\QrCode\Entity;
use RZP\Models\VirtualAccount;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Upi\Icici\Fields;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\Dcs\Configurations;
use RZP\Exception\BadRequestException;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\QrCode\Constants as Constants;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\BharatQr\Constants as BQRConstants;
use RZP\Models\Payment\Processor\TerminalProcessor;
use RZP\Gateway\Upi\Icici\Gateway as IciciGateway;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVAQrEntity;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeWriter;

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

    private $terminalId         = null;

    private $gateway            = null;

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

        $identifier[self::VPA] = $this->getVpaForQr($qrCode);

        $this->trace->info(TraceCode::BHARAT_QR_UPI_IDENTIFIERS,
                           [
                               'qr_code'    => $qrCode->toArrayPublic(),
                               'identifier' => $identifier,
                           ]);

        return $identifier;
    }

    private function getDedicatedTerminalVpaForQr($qrCode, $terminal = null)
    {
        if ($terminal !== null)
        {
            $this->trace->info(TraceCode::QR_CODE_CREATE_TERMINAL, [
                'gateway'     => $terminal->getGateway(),
                'terminal_id' => $terminal->getId(),
                'id'          => $qrCode->getId()
            ]);

            if ($terminal->isShared() === true)
            {
                throw new LogicException('No dedicated terminal found for merchant',
                                         ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND,
                                         [
                                             'terminal_id' => $terminal->getId(),
                                         ]
                );
            }

            $this->gateway = $terminal->getGateway();

            $this->terminalId = $terminal->getId();

            switch ($terminal->getGateway())
            {
                case Gateway::UPI_YESBANK:
                {
                    $variantForFeature = $this->app->razorx->getTreatment($this->merchant->getId(),
                                                                          RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE, $this->mode);

                    if (strtolower($variantForFeature) === RazorxTreatment::RAZORX_VARIANT_ON and
                        ($this->merchant->isFeatureEnabled(FeatureConstants::CLOSE_QR_ON_DEMAND) === true))
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_ON_DEMAND_CLOSE_FOR_YES_BANK);
                    }

                    $vpa = $terminal->getVpa();

                    if ((empty($vpa) === true) or ($vpa === null))
                    {
                        throw new InvalidArgumentException('VPA is required for generating QR');
                    }

                    return $vpa;

                }
                case Gateway::UPI_KOTAK:
                {
                    if ((empty($qrCode->getCloseBy()) === false) or ($this->merchant->isFeatureEnabled(FeatureConstants::CLOSE_QR_ON_DEMAND) === true))
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_CREATE_KOTAK);
                    }

                    $vpa = $terminal->getVpa();

                    if ((empty($vpa) === true) or ($vpa === null))
                    {
                        throw new InvalidArgumentException('VPA is required for generating QR');
                    }

                    return $vpa;
                }

                // For test mode on prod, check vpa/ gatewayMerchantId2 fields whereever the vpa is available.
                case Gateway::SHARP:
                {
                    if (empty($terminal->getVpa()) === false)
                    {
                        return $terminal->getVpa();
                    }
                }

                case Gateway::UPI_MINDGATE:
                {
                    $variantForFeature = $this->app->razorx
                        ->getTreatment(
                            $this->merchant->getId(),
                            RazorxTreatment::DISABLE_QR_CODE_ON_DEMAND_CLOSE,
                            $this->mode
                        );

                    // If there's no expiry passed, this variable will remain true,
                    // else, we will check if the expiry time is beyond 7 days.
                    // If it is beyond 7 days, this variable becomes false.
                    $expirySupport = true;

                    if (empty($qrCode->getCloseBy()) === false)
                    {
                        $expirySupport = $this->checkCloseBySupportForUpiMindgate($qrCode->getCloseBy());
                    }

                    if (($expirySupport !== true) or
                        ((strtolower($variantForFeature) === RazorxTreatment::RAZORX_VARIANT_ON) and
                            ($this->merchant->isFeatureEnabled(FeatureConstants::CLOSE_QR_ON_DEMAND) === true))
                    )
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_QR_CODE_CREATE_HDFC);
                    }

                    $vpa = $terminal->getGatewayMerchantId2();

                    if ((empty($vpa) === true) or ($vpa === null))
                    {
                        throw new InvalidArgumentException('VPA is required for generating QR');
                    }

                    return $vpa;

                }

                case Gateway::UPI_AIRTEL:
                {
                    $vpa = $terminal->getGatewayMerchantId2();

                    if ((empty($vpa) === true) or ($vpa === null))
                    {
                        throw new InvalidArgumentException('VPA is required for generating QR');
                    }

                    return $vpa;
                }

                default:
                {
                    if (empty($terminal->getGatewayMerchantId2()) === false)
                    {
                        return $terminal->getGatewayMerchantId2();
                    }
                }
            }
        }
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
    protected function getUpiQrCode(Entity $qrCode, $terminal = null)
    {
        $this->trace->info(TraceCode::GENERATE_UPI_QR_CODE, [
            'id' => $qrCode->getId()
        ]);

        return $this->getVpaForQr($qrCode, $terminal);
    }

    private function getRefIdForQrCode($qrCode)
    {
        switch ($this->gateway)
        {
            case Gateway::UPI_YESBANK:
                $refId = QrCode\Constants::QR_CODE_V2_YESBANK_PREFIX . $qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX;
                break;

            case Gateway::UPI_MINDGATE:
                if($qrCode->getUsageType() === UsageType::MULTIPLE_USE)
                {
                    $refId = QrCode\Constants::QR_CODE_V2_HDFC_PREFIX . $qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX;
                }
                else
                {
                    $refId =$qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX;
                }
                break;

            case Gateway::UPI_KOTAK:
            case Gateway::UPI_AIRTEL:
                $refId = $qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX;
                break;

            default:
                $refId = self::TR_PREFIX . $qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX;
        }

        if (($this->terminalId === null) or
            ($qrCode->getUsageType() === UsageType::MULTIPLE_USE) or
            ($qrCode->getAmount() === null))
        {
            return $refId;
        }

        $terminal = $this->repo
            ->terminal
            ->getById($this->terminalId);

        return $this->generateRefId($qrCode,$terminal, $refId);
    }

    private function generateRefId($qrCode, $terminal, $refId)
    {
        $terminalDetails = $terminal->toArray();

        if ($this->gateway === Gateway::UPI_MINDGATE)
        {
            $terminalDetails = $terminal->toArrayWithPassword();
        }

        $input = [
            'qr_code'  => $qrCode->toArray(),
            'terminal' => $terminalDetails,
            'merchant' => $qrCode->merchant,
            'amount'   => $qrCode->getRawAmount(),
        ];

        $gatewayClass = $this->app['gateway']->gateway($terminal->getGateway());

        if (method_exists($gatewayClass, 'getQrRefId') === true)
        {
            try
            {
                $gatewayClass->setGatewayParams($input, $this->mode, $terminal);

                $refId = $gatewayClass->getQrRefId($input);

                $qrCode->setGatewayLatencyForQrCreate($gatewayClass->getGatewayTimeTakenMs() ?? 0);

                if ($this->gateway === Gateway::UPI_YESBANK)
                {
                    $refId = QrCode\Constants::QR_CODE_V2_YESBANK_PREFIX . $qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX;
                }
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException($ex,
                    Trace::ERROR,
                    TraceCode::QR_API_REF_ID_GENERATION_FAILED,
                    [
                        'qr_code' => $qrCode->getId(),
                        'gateway' => $this->gateway,
                    ]
                );

                throw new Exception\ServerErrorException('QrCode creation failed due to error at bank or wallet gateway',
                    ErrorCode::BAD_REQUEST_QR_CODE_REF_ID_GENERATION_FAILURE,
                    null,
                    $ex,
                );
            }
        }

        return $refId;
    }

    /**
     * @param $vpa    string
     * @param $qrCode \RZP\Models\QrCode\NonVirtualAccountQrCode\Entity
     */
    private function generateUpiQrIntentUrl($vpa, $qrCode)
    {
        $content = [
            Base\IntentParams::VERSION       => QrCode\Constants::QR_V2_VERSION,
            Base\IntentParams::MODE          => $this->getQrCodeMode($qrCode),
            Base\IntentParams::PAYEE_ADDRESS => $vpa,
            Base\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $this->merchant->getFilteredDba()),
            Base\IntentParams::TXN_REF_ID    => $this->getRefIdForQrCode($qrCode),
            Base\IntentParams::TXN_CURRENCY  => 'INR',
            Base\IntentParams::MCC           => $this->merchant->getCategory(),
            Base\IntentParams::QR_MEDIUM     => QrCode\Constants::QR_V2_QR_MEDIUM,
        ];

        if($qrCode->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::QR_CUSTOM_TXN_NAME) === false)
        {
            $content[Base\IntentParams::TXN_NOTE] = 'Payment to ' . $this->merchant->getFilteredDba();
        }

        if ($qrCode->hasFixedAmount())
        {
            $amount    = $qrCode->getAmount() / 100;
            $rawAmount = $qrCode->getRawAmount();

            $this->trace->info(TraceCode::QR_CODE_FIXED_AMOUNT_DETAILS, [
                'id'                      => $qrCode->getId(),
                'qr_code_amount'          => $qrCode->getAmount(),
                'qr_code_amount(Rs.)'     => $amount,
                'qr_code_raw_amount'      => $rawAmount,
                'qr_code_raw_amount(Rs.)' => $this->formatAmountToRupees($qrCode->getRawAmount()),
            ]);

            if ($this->checkIfExperimentEnabledforAmountMismatchFix($qrCode->getMerchantId()) === true)
            {
                $content[Base\IntentParams::TXN_AMOUNT] = $this->formatAmountToRupees($qrCode->getRawAmount());
            }
            else
            {
                $content[Base\IntentParams::TXN_AMOUNT] = $amount;
            }
        }

        if((str_contains($vpa, '@kotak') === true) and ($qrCode->getUsageType() === UsageType::SINGLE_USE))
        {
            $content[Base\IntentParams::TRANSACTION_ID] = $qrCode->getId();
        }

        if((str_contains($vpa, '@mairtel') === true) and ($qrCode->getUsageType() === UsageType::MULTIPLE_USE))
        {
            unset($content[Base\IntentParams::MODE]);
            unset($content[Base\IntentParams::TXN_REF_ID]);
        }

        $content = array_merge($content, InvoiceDetails::getTaxDetails($qrCode));

        return 'upi://pay?' . str_replace(' ', '', urldecode(http_build_query($content)));
    }

    public function checkIfExperimentEnabledforAmountMismatchFix($merchantId)
    {
        $variant = $this->app['razorx']->getTreatment($merchantId,
                                                      RazorxTreatment::QR_AMOUNT_MISMATCH_FIX,
                                                      $this->mode);

        if (strtolower($variant) === RazorxTreatment::RAZORX_VARIANT_ON)
        {
            return true;
        }

        return false;
    }

    public function fetchDedicatedTerminalFromQrString($qrCode)
    {
        $vpa  = $qrCode->getQrVpa();

        if (str_contains($qrCode['qr_string'], '@icici') === true)
        {
            $gateway = GATEWAY::UPI_ICICI;
            $params  = array(Terminal\Entity::GATEWAY_MERCHANT_ID2 => $vpa);
        }
        elseif (str_contains($qrCode['qr_string'], '@yesbank') === true)
        {
            $gateway = GATEWAY::UPI_YESBANK;
            $params  = array(Terminal\Entity::VPA => $vpa);
        }
        elseif(str_contains($qrCode['qr_string'], '@hdfcbank') === true)
        {
            $gateway = GATEWAY::UPI_MINDGATE;
            $params  = array(Terminal\Entity::GATEWAY_MERCHANT_ID2 => $vpa);
        }
        elseif (str_contains($qrCode['qr_string'], '@mairtel') === true)
        {
            $gateway = GATEWAY::UPI_AIRTEL;
            $params  = array(Terminal\Entity::GATEWAY_MERCHANT_ID2 => $vpa);
        }
        else
        {
            $gateway = GATEWAY::SHARP;
            $params  = array(Terminal\Entity::VPA => $vpa);
        }

        $this->trace->info(TraceCode::QR_CODE_FETCH_TERMINAL_PARAMS, ['gateway' => $gateway, 'params' => $params]);

        $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $params);

        if (($terminal instanceof Terminal\Entity) === false)
        {
            throw new Exception\LogicException(
                TraceCode::QR_CODE_UPI_QR_TERMINAL_NOT_FOUND_FOR_MERCHANT,
                Error\ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND,
                [
                    'merchant_id' => $qrCode->merchant->getId(),
                ]);
        }

        return $terminal;
    }

    protected function getImageDimensions($gdImage)
    {
        $width = imagesx($gdImage);

        $height = imagesy($gdImage);

        return [$width, $height];
    }

    protected function setLogoOnQrBaseImage($logo, $baseImage, $baseImageWidth, $baseImageHeight)
    {
        $topMargin = Constants::TOP_MARGIN_IN_PIXEL_NPCI;

        $height = Constants::HEIGHT_OF_LOGO_IN_PIXEL_NPCI;

        list($originalLogoWidth, $originalLogoHeight) = $this->getImageDimensions($logo);

        $aspectRatio = $originalLogoWidth / $originalLogoHeight;

        $newLogoWidth = 4 * $height * $aspectRatio;

        $newLogoHeight = 4 * $height;

        $resizedLogo = imagecreatetruecolor($newLogoWidth, $newLogoHeight);

        $transparentColor = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);

        imagecolortransparent($resizedLogo, $transparentColor);

        imagealphablending($resizedLogo, false);

        imagesavealpha($resizedLogo, true);

        // Fill the resized logo with the transparent color
        imagefilledrectangle($resizedLogo, 0, 0, $newLogoWidth, $newLogoHeight, $transparentColor);

        imagecopyresampled($resizedLogo, $logo, 0, 0, 0, 0, $newLogoWidth, $newLogoHeight, $originalLogoWidth, $originalLogoHeight);

        $destX = ($baseImageWidth - $newLogoWidth) / 2;

        $destY = 4 * $topMargin;

        // Merge the resized logo onto the base image with specified opacity
        $this->imagecopymerge_alpha(
            $baseImage, $resizedLogo, $destX, $destY,
            0, 0, $newLogoWidth, $newLogoHeight,
            Constants::OPACITY
        );
    }

    /**
     * Retrieve and return the appropriate logo image based on the feature flag status
     * and the application environment (unit test or production).
     */
    protected function getLogoForFeatureFlag()
    {
        $logoUrl = null;

        $rectangularLogoUrl =  $this->merchant->getRectangularLogoUrl();

        $rectangularLogoImage = $rectangularLogoUrl === null ? false :imagecreatefrompng($rectangularLogoUrl);

        if ($this->merchant->isCustomMerchantUpiQrEnabled() === true)
        {
            $logoUrl = $this->app->runningUnitTests() ?
                public_path() . $this->merchant->getLogoUrl()
                :  ($rectangularLogoImage === false ? $this->merchant->getFullLogoUrlWithSize() : $rectangularLogoUrl);
        }
        else
        {
            $logoUrl = $this->app->runningUnitTests() ?
                public_path() . $this->merchant->org->getMainLogo() :
                $this->merchant->org->getMainLogo();
        }

        return imagecreatefrompng($logoUrl);
    }


    protected function setNameAndDescription(& $logoImage, & $displayDetails, & $qrCode)
    {
        $color = imagecolorallocate($logoImage, 4, 9, 63);

        $ypos = QrCode\Constants::QR_V2_UPI_QR_NAME_YPOS_KOTAK;

        $name = $displayDetails['name'];

        $description = $qrCode->getDescription();

        if(strlen($name) > 0)
        {
            $this->alignCentre($logoImage, $displayDetails['name'], $color, Constants::QR_VPA_FONT, $ypos, 40, 20);
        }
        if(strlen($description) > 0)
        {
            $this->alignCentre($logoImage, $qrCode->getDescription(), $color, Constants::QR_VPA_FONT, $ypos, 25, 40);
        }
    }

    protected function shouldUseCustomUpiQr(): bool
    {
        return $this->merchant->isCustomOrgUpiQrEnabled() || $this->merchant->isCustomMerchantUpiQrEnabled();
    }

    protected function isHdfcOrg($org): bool
    {
        if($this->app->runningUnitTests() === true)
        {
            return (substr(strtolower($org->getDisplayName()), 0, 4) === "hdfc");
        }
        else
        {
            $customCode = $org->getCustomCode();

            return $customCode === 'hdfc' or $customCode === 'HDFC' or $customCode === 'HDFC GIG' or $customCode === 'HDFC CTSP';
        }
    }

    private function isKotakOrg($org): bool
    {
        if($this->app->runningUnitTests() === true)
        {
            return (substr(strtolower($org->getDisplayName()), 0, 5) === "kotak");
        }
        else
        {
            $customCode = $org->getCustomCode();

            return $customCode === 'KKBK';
        }

    }

    private function getBaseImagePath(): string
    {
        $org = $this->merchant->org;

        $isHdfc = $this->isHdfcOrg($org);

        $isKotak = $this->isKotakOrg($org);

        if ($isHdfc === true)
        {
            return $this->getImagePathFromOrg($org);
        }
        else if($isKotak === true)
        {
            return '/img/custom_upi_qr_image.png';
        }

        return '/img/standard_base_image.png';
    }
    private function setLogoInMiddleOfQr(& $qrCodeImage, & $displayDetails)
    {
        if($this->app->runningUnitTests() === true)
        {
            $this->setMerchantLogoInQrImage(public_path() . '/img/facebook.png', $qrCodeImage);
        }
        else
        {
            $this->setMerchantLogoInQrImage($displayDetails['logo'], $qrCodeImage);
        }
    }

    private function generateCustomUpiQrCodeImage($qrCode, $localFilePath, $qrCodeImage, $displayDetails)
    {
        $path = $this->getBaseImagePath();

        $logo = $this->getLogoForFeatureFlag();

        if ($logo === false)
        {
            $path = $this->getImagePathFromOrg($this->merchant->org);
        }

        $logoImage = imagecreatefrompng(public_path(). $path);

        $this->setLogoInMiddleOfQr($qrCodeImage, $displayDetails);

        imageAlphaBlending($logoImage, true);

        imageSaveAlpha($logoImage, true);

        $this->imagecopymerge_alpha($logoImage, $qrCodeImage,
            Constants::QR_V2_UPI_QR_DEST_X, Constants::QR_V2_UPI_QR_DEST_Y,
            Constants::SORCE_X, Constants::SORCE_Y,
            Constants::QR_V2_UPI_QR_CODE_WIDTH, Constants::QR_V2_UPI_QR_CODE_HEIGHT,
            Constants::OPACITY);

        list($baseImageWidth, $baseImageHeight) = $this->getImageDimensions($logoImage);

        if($logo !== false and  $this->isHdfcOrg($this->merchant->org) === false)
        {
            $this->setLogoOnQrBaseImage($logo, $logoImage, $baseImageWidth, $baseImageHeight);
        }

        $vpa = $qrCode->getQrVpa();

        $color = imagecolorallocate($logoImage, 4, 9, 63);

        $yposUpi = $baseImageHeight / 1.5;

        if(($this->isHdfcOrg($this->merchant->org) === false) and $path!=='new_upi_qr.png')
        {
            $this->alignCentre($logoImage, "UPI ID: $vpa", $color, Constants::QR_VPA_FONT, $yposUpi, 15, 40);
        }

        $this->setNameAndDescription($logoImage, $displayDetails, $qrCode);

        imagepng($logoImage, $localFilePath);

        imagedestroy($logoImage);

        imagedestroy($qrCodeImage);

        return $localFilePath;
    }

    public function generateUpiQrCodeImage($qrCode)
    {
        $localFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '.' . Constants::QR_CODE_EXTENSION;

        $qrCodeImage = $this->getQrCodeStringAndGenerateImage($qrCode);

        $displayDetails = $this->getMerchantDisplayDetails();

        $this->setLogoInMiddleOfQr($qrCodeImage, $displayDetails);

        if($this->shouldUseCustomUpiQr() === true)
        {
            return $this->generateCustomUpiQrCodeImage($qrCode, $localFilePath, $qrCodeImage, $displayDetails);
        }

        if($this->merchant->org->isFeatureEnabled(\RZP\Models\Feature\Constants::ORG_CUSTOM_UPI_LOGO) === true)
        {
            $path = $this->getImagePathFromOrg($this->merchant->org);
        }
        else
        {
            $path = '/img/new_upi_qr.png';
        }

        $logoImage = imagecreatefrompng(public_path() . $path);

        imageAlphaBlending($logoImage, true);

        imageSaveAlpha($logoImage, true);

        $this->imagecopymerge_alpha($logoImage, $qrCodeImage,
                                    Constants::QR_V2_UPI_QR_DEST_X, Constants::QR_V2_UPI_QR_DEST_Y,
                                    Constants::SORCE_X, Constants::SORCE_Y,
                                    Constants::QR_V2_UPI_QR_CODE_WIDTH, Constants::QR_V2_UPI_QR_CODE_HEIGHT,
                                    Constants::OPACITY);

        $color = imagecolorallocate($logoImage, 4, 9, 63);

        $ypos = QrCode\Constants::QR_V2_UPI_QR_NAME_YPOS;

        $this->setNameAndDescription($logoImage, $displayDetails, $qrCode);

        imagepng($logoImage, $localFilePath);

        imagedestroy($logoImage);

        imagedestroy($qrCodeImage);

        return $localFilePath;
    }

    public function getPreviewImage()
    {
        $localFilePath = $this->getLocalSaveDir() . '/' . 'preview_' . time() . '.' . Constants::QR_CODE_EXTENSION;

        $path = $this->getBaseImagePath();

        $logo = $this->getLogoForFeatureFlag();

        if ($logo === false)
        {
            $path = $this->getImagePathFromOrg($this->merchant->org);
        }

        $logoImage = imagecreatefrompng(public_path(). $path);

        imageAlphaBlending($logoImage, true);

        imageSaveAlpha($logoImage, true);

        list($baseImageWidth, $baseImageHeight) = $this->getImageDimensions($logoImage);

        if($logo !== false and  $this->isHdfcOrg($this->merchant->org) === false)
        {
            $this->setLogoOnQrBaseImage($logo, $logoImage, $baseImageWidth, $baseImageHeight);
        }

        $color = imagecolorallocate($logoImage, 4, 9, 63);

        imagepng($logoImage, $localFilePath);

        imagedestroy($logoImage);

        return $localFilePath;
    }

    protected function getImagePathFromOrg($org)
    {
        $defaultPath = '/img/new_upi_qr.png';

        $name = strtolower(str_replace(' ', '_',$org->getDisplayName()));

        $path = '/img/new_upi_qr_' . $name . '.png';

        if(file_exists(public_path().$path) === true)
            return $path;
        else
            return $defaultPath;
    }

    public function generateBharatQrCodeImage($qrCode)
    {
        $localFilePathQrBasicFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '_basic.png';

        $qrCodeWriter = QrCodeWriter::format('png');

        $qrCodeWriter->size(Constants::QR_CODE_SIZE)
                     ->errorCorrection('M')
                     ->generate($qrCode->getQrString(), $localFilePathQrBasicFilePath);

        $qrCodeImage = imagecreatefrompng($localFilePathQrBasicFilePath);

        $localFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '.' . Constants::QR_CODE_EXTENSION;

        $logoImage = imagecreatefromjpeg(public_path() . '/img/qr.jpg');

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
        $localFilePathQrBasicFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '_basic.png';

        $qrCodeWriter = QrCodeWriter::format('png');

        $qrCodeWriter->size(Constants::QR_V2_UPI_QR_CODE_SIZE)
                     ->errorCorrection('M')
                     ->generate($qrCode->getQrString(), $localFilePathQrBasicFilePath);

        $qrCodeImage = imagecreatefrompng($localFilePathQrBasicFilePath);

        return $qrCodeImage;
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
            return ((($partner->isAggregatorPartner() === true)
                     or ($partner->isFullyManagedPartner() === true))
                    and ($partner->isFeatureEnabled(Feature\Constants::QR_IMAGE_PARTNER_NAME) === true));
        })->last();

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

    /**
     * @param Entity $qrCode
     *
     * @return mixed|string|null
     * @throws BadRequestException
     * @throws InvalidArgumentException
     */
    protected function getVpaForQr(Entity $qrCode, $terminal = null)
    {
        $vpa = null;

        if ($this->checkIfDedicatedTerminalSplitzExperimentEnabled($qrCode->merchant->getId()) === true)
        {
            $terminals = $terminal === null ? $this->getDedicatedTerminalForQrCreate($qrCode) : [0 => $terminal];

            $errorMessage = '';
            $errorCode    = '';
            foreach ($terminals as $terminal)
            {
                try
                {
                    $vpa = $this->getDedicatedTerminalVpaForQr($qrCode, $terminal);

                    if ($vpa !== null)
                    {
                        if ($qrCode->getProvider() === Provider::UPI_QR)
                        {
                            return $this->generateUpiQrIntentUrl($vpa, $qrCode);
                        }
                        else
                        {
                            return $vpa;
                        }
                    }
                }
                catch (\Exception $e)
                {
                    $errorMessage = $e->getMessage();
                    $errorCode    = $e->getCode();

                    $this->trace->traceException($e);
                }
            }

            if (($errorMessage) !== '')
            {
                throw new BadRequestException($errorCode, $errorMessage);
            }

            if (empty($vpa) === true)
            {
                throw new InvalidArgumentException('VPA is required for generating QR');
            }
        }
        else
        {
            $vpa = $this->generateVpaForQr($qrCode);

            if ($qrCode->getProvider() === Provider::UPI_QR)
            {
                return $this->generateUpiQrIntentUrl($vpa, $qrCode);
            }
        }

        return $vpa;
    }

    public function checkIfDedicatedTerminalSplitzExperimentEnabled($merchantId)
    {
        try
        {
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $this->app['config']->get('app.dedicated_terminal_qr_code_splitz_experiment_id'),
                'request_data'  => json_encode(['merchant_id' => $merchantId]),
            ];
            $response   = $this->app['splitzService']->evaluateRequest($properties);

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, [
                'experiment_id' => $properties['experiment_id'],
                'merchant_id'   => $merchantId,
                '$response'     => $response
            ]);

            if ($response['response']['variant'] !== null)
            {
                $variables = $response['response']['variant']['variables'] ?? [];

                foreach ($variables as $variable)
                {
                    $key   = $variable['key'] ?? '';
                    $value = $variable['value'] ?? '';
                    if ($key == "result" && $value == "on")
                    {
                        return true;
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::DEDICATED_TERMINAL_QR_CODE_SPLITZ_ERROR
            );
        }

        return false;
    }

    /**
     * @param Entity $qrCode
     *
     * @return mixed
     */
    protected function getDedicatedTerminalForQrCreate(Entity $qrCode)
    {
        //@todo:: Check for static and dynamic QR. For static QR, terminal type offline should be passed
        $terminals = (new VirtualAccount\Provider())->getTerminalForMethod(Payment\Method::UPI, $qrCode);

        $dedicatedTerminals = array_filter($terminals, function($terminal)
        {
            return (($terminal != null) and ($terminal->isShared() === false));
        });

        if (count($dedicatedTerminals) === 0)
        {
            throw new LogicException('No dedicated terminal found for merchant',
                                     ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND,
                                     [
                                         'fetched_terminals' => $terminals
                                     ]
            );
        }

        return $dedicatedTerminals;
    }

    /**
     * Function formatAmount() is giving incorrect result for amount=27071 (270.7)
     * Formats amount to 2 decimal places
     * @param  string $amount amount in paise (27071)
     * @return string amount formatted to 2 decimal places in INR (270.71)
     */
    public function formatAmountToRupees($amount): string
    {
        return substr_replace((string) $amount, '.', -2, 0);
    }

    /**
     * upi_mindgate gateway does not support the expiry of QRs beyond 7 days from current time.
     * This function checks if the close_by time of the QR code is 7 days after current time or not.
     * @param int $closeBy the timestamp which needs to be checked for expiry
     *
     * @return bool true if upi_mindgate supports this expiry, false otherwise
     */
    protected function checkCloseBySupportForUpiMindgate(int $closeBy): bool
    {
        $variantForExpiry = $this->app->razorx
            ->getTreatment(
                $this->merchant->getId(),
                RazorxTreatment::HDFC_QR_EXPIRY,
                $this->mode
            );

        if ($variantForExpiry !== strtolower(RazorxTreatment::RAZORX_VARIANT_ON))
        {
            return false;
        }

        $timeAfter7Days = Carbon::now(Timezone::IST)->addDays(7)->timestamp;

        return ($closeBy < $timeAfter7Days);
    }
}
