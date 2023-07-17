<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

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
use RZP\Exception\BadRequestException;
use RZP\Models\VirtualAccount\Provider;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\InvalidArgumentException;
use RZP\Models\QrCode\Constants as Constants;
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

                // For test mode on prod, check vpa/ gatewayMerchantId2 fields whereever the vpa is available.
                case Gateway::SHARP:
                {
                    if (empty($terminal->getVpa()) === false)
                    {
                        return $terminal->getVpa();
                    }
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
    protected function getUpiQrCode(Entity $qrCode)
    {
        $this->trace->info(TraceCode::GENERATE_UPI_QR_CODE, [
            'id' => $qrCode->getId()
        ]);

        return $this->getVpaForQr($qrCode);
    }

    private function getRefIdForQrCode($qrCode)
    {
        switch ($this->gateway)
        {
            case Gateway::UPI_YESBANK:
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

        return $this->generateRefId($qrCode,$terminal);
    }

    private function generateRefId($qrCode, $terminal)
    {
        $input = [
            'qr_code'  => $qrCode->toArray(),
            'terminal' => $terminal->toArray(),
            'merchant' => $qrCode->merchant,
        ];

        $gatewayClass = $this->app['gateway']->gateway($terminal->getGateway());

        if (method_exists($gatewayClass, 'getQrRefId') === true)
        {
            try
            {
                $gatewayClass->setGatewayParams($input, $this->mode, $terminal);

                $refId = $gatewayClass->getQrRefId($input);

                if ($this->gateway === Gateway::UPI_YESBANK)
                {
                    $refId = $qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX;
                }
            }
            catch (\Exception $ex)
            {
                throw new BadRequestException('QrCode creation failed due to error at bank or wallet gateway',
                                              ErrorCode::BAD_REQUEST_QR_CODE_REF_ID_GENERATION_FAILURE,
                                              null,
                                              null);
            }
        }

        return $refId;
    }

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
            $content[Base\IntentParams::TXN_AMOUNT] = $qrCode->getAmount() / 100;
        }

        $content = array_merge($content, InvoiceDetails::getTaxDetails($qrCode));

        return 'upi://pay?' . str_replace(' ', '', urldecode(http_build_query($content)));
    }

    public function generateUpiQrCodeImage($qrCode)
    {
        $localFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '.' . Constants::QR_CODE_EXTENSION;

        $qrCodeImage = $this->getQrCodeStringAndGenerateImage($qrCode);

        $displayDetails = $this->getMerchantDisplayDetails();

        $this->setMerchantLogoInQrImage($displayDetails['logo'], $qrCodeImage);

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

        $this->alignCentre($logoImage, $displayDetails['name'], $color, 'Mulish-ExtraBold.ttf', $ypos, 40, 20);

        $this->alignCentre($logoImage, $qrCode->getDescription(), $color, 'Mulish-SemiBold.ttf', $ypos, 25, 40);

        imagepng($logoImage, $localFilePath);

        imagedestroy($logoImage);

        imagedestroy($qrCodeImage);

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
    protected function getVpaForQr(Entity $qrCode)
    {
        $vpa = null;

        if ($this->checkIfDedicatedTerminalSplitzExperimentEnabled($qrCode->merchant->getId()) === true)
        {
            $terminals = $this->getDedicatedTerminalForQrCreate($qrCode);

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

        $dedicatedTerminals = array_filter($terminals, function(Terminal\Entity $terminal)
        {
            return ($terminal->isShared() === false);
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
}
