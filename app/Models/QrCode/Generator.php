<?php

namespace RZP\Models\QrCode;

use App;
use Lib\CRC16;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use BaconQrCode\Writer;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use BaconQrCode\Renderer;
use RZP\Models\Card\Network;
use RZP\Models\BharatQr\Tags;
use RZP\Models\VirtualAccount;
use RZP\Models\FileStore\Utility;
use RZP\Models\BharatQr\Constants as BQRConstants;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeWriter;

class Generator extends Base\Core
{

    public function generateBharatQrCodeImage($qrCode)
    {
        $logoImage = imagecreatefromjpeg(public_path() . '/img/qr.jpg');

        $localFilePathQrBasicFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '_basic.png';

        $qrCodeWriter = QrCodeWriter::format('png');

        $qrCodeWriter->size(Constants::QR_CODE_SIZE)
                     ->format('png')
                     ->errorCorrection('M')
                     ->generate($qrCode->getQrString(), $localFilePathQrBasicFilePath);

        $qrCodeImage = imagecreatefrompng($localFilePathQrBasicFilePath);

        imagecopymerge($logoImage, $qrCodeImage,
                       Constants::QR_DEST_X, Constants::QR_DEST_Y,
                       Constants::SORCE_X, Constants::SORCE_Y,
                       Constants::QR_CODE_WIDTH, Constants::QR_CODE_HEIGHT,
                       Constants::OPACITY);

        $localFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '.' . Constants::QR_CODE_EXTENSION;

        imagejpeg($logoImage, $localFilePath);

        imagedestroy($logoImage);

        imagedestroy($qrCodeImage);

        return $localFilePath;
    }

    public function generateUpiQrCodeImage($qrCode)
    {
        $localFilePathQrBasicFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '_basic.png';

        $qrCodeWriter = QrCodeWriter::format('png');

        $qrCodeWriter->size(Constants::QR_CODE_SIZE)
                     ->format('png')
                     ->errorCorrection('M')
                     ->generate($qrCode->getQrString(), $localFilePathQrBasicFilePath);

        $qrCodeImage = imagecreatefrompng($localFilePathQrBasicFilePath);

        $logoImage = imagecreatefromjpeg(public_path() . '/img/upi_qr.jpg');

        imagecopymerge($logoImage, $qrCodeImage,
                       Constants::UPI_QR_DEST_X, Constants::UPI_QR_DEST_Y,
                       Constants::SORCE_X, Constants::SORCE_Y,
                       Constants::UPI_QR_CODE_WIDTH, Constants::UPI_QR_CODE_HEIGHT,
                       Constants::OPACITY);

        $localFilePath = $this->getLocalSaveDir() . '/' . $qrCode->getId() . '.' . Constants::QR_CODE_EXTENSION;

        imagejpeg($logoImage, $localFilePath);

        imagedestroy($logoImage);

        imagedestroy($qrCodeImage);

        return $localFilePath;
    }

    public function getLocalSaveDir(): string
    {
        $dirPath = storage_path('files/qrcodes');

        if (file_exists($dirPath) === false)
        {
            (new Utility)->callFileOperation('mkdir', [$dirPath, 0777, true]);
        }

        return $dirPath;
    }

    public function generateQrString(Entity $qrCode)
    {
        $provider = $qrCode->getProvider();

        switch ($provider)
        {
            case Type::BHARAT_QR :
                return $this->getBharatQrCode($qrCode);

            case Type::UPI_QR:
                return $this->getUpiQrCode($qrCode);

            default :
                return '';
        }
    }

    protected function getUpiQrCode(Entity $qrCode)
    {
        $this->trace->info(TraceCode::GENERATE_UPI_QR_CODE, $qrCode->toArrayPublic());

        $terminal = (new VirtualAccount\Provider())->getTerminalForMethod(Payment\Method::UPI, $qrCode, null, [
            'flow' => 'intent'
        ]);

        if (($terminal instanceof Terminal\Entity) === false)
        {
            throw new Exception\LogicException(TraceCode::QR_CODE_UPI_QR_TERMINAL_NOT_FOUND_FOR_MERCHANT,
                                               Error\ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND,
                                               [
                                                   'merchant_id' => $qrCode->merchant->getId(),
                                               ]);
        }

        // Once we have mocked the complete payment, we can call gateway
        $gatewayInput = [
            'payment'  => [
                Payment\Entity::ID            => $qrCode->getId(),
                Payment\Entity::AMOUNT        => $qrCode->getAmount(),
                Payment\Entity::CURRENCY      => 'INR',
                Payment\Entity::DESCRIPTION   => $qrCode->source->description ?? '',
                Payment\Entity::RECEIVER_TYPE => Constants::QR_CODE,
                Payment\Entity::RECEIVER_ID   => $qrCode->getId(),
            ],
            'merchant' => $qrCode->merchant,
            'terminal' => $terminal
        ];

        if($qrCode->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::UPIQR_V1_HDFC) === true)
        {
            $gatewayInput['usage_type'] = $qrCode->getUsageType() ;
        }

        if ($qrCode->source !== null and $qrCode->source->hasOrder() === true)
        {
            $gatewayInput['order'] = $qrCode->source->entity;
        }

        $this->trace->info(TraceCode::QR_CODE_UPI_GATEWAY_CALL_FOR_INTENT_URL, [
            'gateway_input' => $gatewayInput,
            'terminal'      => $terminal->getId()
        ]);

        // Any exception on gateway will rollback the process right away
        $response = app('gateway')->call($terminal->getGateway(), 'get_intent_url', $gatewayInput, $this->mode, $terminal);

        // To Signed terminals, gateway will return qr_code_url along with intent_url
        // otherwise gateway will only return intent_url, And qr_code_url is for QR.
        if (isset($response['data']['qr_code_url']))
        {
            return $response['data']['qr_code_url'];
        }
        else
        {
            if (isset($response['data']['intent_url']))
            {
                return $response['data']['intent_url'];
            }
        }
    }

    protected function getBharatQrCode($qrCode)
    {
        $this->trace->info(TraceCode::GENERATE_BHARAT_QR_CODE, $qrCode->toArrayPublic());

        $pointOfInitiation = $this->getPointOfInitiation($qrCode);

        $merchantIdentifiers = $this->generateBharatQrMerchantIdentifier($qrCode);

        $merchantDetails = $this->getMerchantDetailsToPopulate($qrCode);

        $tagArray = [
            Tags::VERSION . $this->getLengthAndValue(BQRConstants::VERSION),
            Tags::POINT_OF_INITIATION . $this->getLengthAndValue($pointOfInitiation),
            $this->getIdentifierTlv(Tags::VISA, Terminal\Entity::VISA_MPAN, $merchantIdentifiers),
            $this->getIdentifierTlv(Tags::MASTERCARD, Terminal\Entity::MC_MPAN, $merchantIdentifiers),
            $this->getIdentifierTlv(Tags::RUPAY, Terminal\Entity::RUPAY_MPAN, $merchantIdentifiers),
            $this->getMerchantAccountIdentifier($qrCode),
            $this->getBharatQrUpiTlv($qrCode, $merchantIdentifiers),
            $this->getBharatQrDynamicUpiTlv($qrCode, $merchantIdentifiers),
            Tags::MERCHANT_CATEGORY . $this->getLengthAndValue($merchantDetails[Constants::MERCHANT_CATEGORY]),
            Tags::CURRENCY_CODE . $this->getLengthAndValue(BQRConstants::CURRENCY_CODE),
            $this->getBharatQrAmountTlv($qrCode),
            Tags::COUNTRY_CODE . $this->getLengthAndValue(BQRConstants::COUNTRY_CODE),
            Tags::MERCHANT_NAME . $this->getLengthAndValue($merchantDetails[Constants::MERCHANT_NAME]),
            Tags::MERCHANT_CITY . $this->getLengthAndValue($merchantDetails[Constants::MERCHANT_CITY]),
            Tags::MERCHANT_PIN_CODE . $this->getLengthAndValue($merchantDetails[Constants::MERCHANT_PINCODE]),
            $this->getBharatQrAdditionalDetailTlv($qrCode, $merchantIdentifiers),
        ];

        $qrString = implode('', $tagArray);

        // This is the CRC TL. Length of CRC is always 4
        $qrString .= Tags::CRC . '04';

        $crc = (new CRC16)->calculateCrc($qrString);

        $qrString .= $crc;

        return $qrString;
    }

    protected function getMerchantDetailsToPopulate($qrCode): array
    {
        $defaultAttributes = $this->getDefaultMerchantDetails();

        $merchant = $qrCode->merchant;

        $merchantAttributes = [
            Constants::MERCHANT_CATEGORY => $merchant->getCategory(),
            Constants::MERCHANT_NAME     => $merchant->getDbaName(),
        ];

        $attributes = array_merge($defaultAttributes, array_filter($merchantAttributes));

        if ($merchant->merchantDetail !== null)
        {
            $merchantDetail = $merchant->merchantDetail;

            $merchantDetailAttributes = [
                Constants::MERCHANT_CITY    => $merchantDetail->getBusinessRegisteredCity(),
                Constants::MERCHANT_PINCODE => $merchantDetail->getBusinessRegisteredPin(),
            ];

            $attributes = array_merge($attributes, array_filter($merchantDetailAttributes));
        }

        return $attributes;
    }

    protected function getDefaultMerchantDetails(): array
    {
        return [
            Constants::MERCHANT_CATEGORY => BQRConstants::MERCHANT_CATEGORY,
            Constants::MERCHANT_NAME     => BQRConstants::MERCHANT_NAME,
            Constants::MERCHANT_CITY     => BQRConstants::MERCHANT_CITY,
            Constants::MERCHANT_PINCODE  => BQRConstants::MERCHANT_PINCODE,
        ];
    }

    protected function getIdentifierTlv(string $tag, string $networkMpan, array $merchantIdentifiers)
    {
        if (empty($merchantIdentifiers[$networkMpan]) === false)
        {
            return $tag . $this->getLengthAndValue($merchantIdentifiers[$networkMpan]);
        }

        return null;
    }

    protected function getMerchantAccountIdentifier($qrCode)
    {
        $value = BQRConstants::IFSC_CODE . BQRConstants::ACCOUNT_NUMBER;

        return Tags::MERCHANT_ACCOUNT . $this->getLengthAndValue($value);
    }

    protected function getPointOfInitiation(Entity $qrCode)
    {
        if (empty($qrCode->getAmount()) === true)
        {
            return BQRConstants::STATIC_POI;
        }

        // Dynamic code always have amount tag
        return BQRConstants::DYNAMIC_POI;
    }

    protected function getBharatQrUpiTlv(Entity $qrCode, array $merchantIdentifiers)
    {
        $merchantVpa = $merchantIdentifiers[Terminal\Entity::VPA] ?? null;

        // This happens when no terminal of upi
        // bqr is assigned to the merchant.
        if (empty($merchantVpa) === true)
        {
            return null;
        }

        $rupayRidTlv    = Tags::UPI_VPA_RUPAY_RID . $this->getLengthAndValue(BQRConstants::RUPAY_RID);
        $merchantVpaTlv = Tags::UPI_VPA_MERCHANT_VPA . $this->getLengthAndValue($merchantVpa);

        $amountTlv = '';

        $amount = (string) ($qrCode->getFormattedAmount());

        if (empty($amount) === false)
        {
            $amountTlv = Tags::UPI_VPA_AMOUNT . $this->getLengthAndValue($amount);
        }

        $upiString = $rupayRidTlv . $merchantVpaTlv . $amountTlv;

        return Tags::UPI_VPA . strlen($upiString) . $upiString;
    }

    protected function getBharatQrDynamicUpiTlv(Entity $qrCode, array $merchantIdentifiers)
    {
        $merchantVpa = $merchantIdentifiers[Terminal\Entity::VPA] ?? null;

        // This happens when no terminal of upi
        // bqr is assigned to the merchant.
        if (empty($merchantVpa) === true)
        {
            return null;
        }

        $rupayRidTlv = Tags::UPI_VPA_RUPAY_RID . $this->getLengthAndValue(BQRConstants::RUPAY_RID);

        //
        // In case of upi payments we need to send reference with
        // prefix. This is how they identify our payments
        //
        $transactionReferenceTlv = $this->getTransactionReferenceTlv($qrCode);

        $upiString = $rupayRidTlv . $transactionReferenceTlv;

        return Tags::UPI_VPA_REFERENCE . strlen($upiString) . $upiString;
    }

    protected function getTransactionReferenceTlv($qrCode)
    {
        return Tags::UPI_VPA_REFERENCE_TR . $this->getLengthAndValue(BQRConstants::UPI_PREFIX . $qrCode->getId());
    }

    protected function getBharatQrAdditionalDetailTlv(Entity $qrCode, array $merchantIdentifiers)
    {
        $idTlv = Tags::ADDITIONAL_DETAIL_ID . $this->getLengthAndValue($qrCode->getId());

        if (isset($merchantIdentifiers['rupay_tid']) === true)
        {
            $terminalIdTlv = Tags::TERMINAL_ID . $this->getLengthAndValue($merchantIdentifiers['rupay_tid']);

            $idTlv .= $terminalIdTlv;
        }

        $additionalDetailsString = $idTlv;

        return Tags::ADDITIONAL_DETAIL . strlen($additionalDetailsString) . $additionalDetailsString;
    }

    protected function getBharatQrAmountTlv(Entity $qrCode)
    {
        $amount = (string) ($qrCode->getFormattedAmount());

        if (empty($amount) === true)
        {
            return '';
        }

        return Tags::AMOUNT . $this->getLengthAndValue($amount);
    }

    protected function getLengthAndValue(string $str)
    {
        return str_pad(strlen($str), 2, '0', STR_PAD_LEFT) . $str;
    }

    /**
     * This will generate merchant identifier using network
     * network could be Visa , MasterCard or Rupay
     *
     * @param Entity $qrCode
     *
     * @return array
     * @throws Exception\LogicException
     */
    protected function generateBharatQrMerchantIdentifier(Entity $qrCode)
    {
        $cardIdentifiers = array_filter($this->getCardIdentifiers($qrCode));

        $upiIdentifier = array_filter($this->getUpiIdentifier($qrCode));

        $allIdentifiers = array_merge($cardIdentifiers, $upiIdentifier);

        //
        // This is important to be here for the calling function.
        //
        if (count(array_filter($allIdentifiers)) === 0)
        {
            throw new Exception\LogicException(
                'No identifiers found for the merchant',
                null,
                [
                    'qr_code' => $qrCode->toArray()
                ]);
        }

        return $allIdentifiers;
    }

    protected function getCardIdentifiers(Entity $qrCode): array
    {
        $app = App::getFacadeRoot();

        // If cards aren't enabled at all, we skip addition of card identifiers
        if ((new Merchant\Methods\Service())->isMethodEnabledForMerchant(Payment\Method::CARD, $qrCode->merchant) === false)
        {
            return [];
        }

        $identifiers = [];

        $bharatQrNetworks = Payment\Gateway::getBharatQrCardNetworks();

        foreach ($bharatQrNetworks as $bharatQrNetwork)
        {
            $mpanAttr = strtolower($bharatQrNetwork) . '_mpan';

            $terminal = (new VirtualAccount\Provider())->getTerminalForMethod(Payment\Method::CARD, $qrCode, $bharatQrNetwork);

            //
            // For a given network, we may not get any terminal at all. This is okay.
            // If we don't, we just search for the next network's terminal
            //
            if ($terminal === null)
            {
                continue;
            }

            if ($bharatQrNetwork === Network::RUPAY)
            {
                $identifiers['rupay_tid'] = $terminal->getGatewayTerminalId();
            }

            $terminal = $terminal->toArray();

            // Note: terminal at this point will have tokenized mpans, we are storing tokenized mpans in qr_string,
            // We will be detokenizing them as and when required (to generate actual qr_string for qr_code)
            $identifiers[$mpanAttr] = $terminal[$mpanAttr];

            // If the mpan is tokenized, detokenize it
            if ((empty($identifiers[$mpanAttr]) === false) and (strlen($identifiers[$mpanAttr]) !== 16))
            {
                $identifiers[$mpanAttr] = $app['mpan.cardVault']->detokenize($identifiers[$mpanAttr]);
            }
            /*
             * Masterpass specifications indicate only 15 digits of mastercard mpan be populated in the qr
             * string. The last digit is generated by the bank/app at the time of scanning and validated using
             * luhn formula. Since many apps follows Masterpass specifications, we are making this change at
             * our end as well.
             */

            if ($bharatQrNetwork === Network::MC)
            {
                $identifiers[$mpanAttr] = substr($identifiers[$mpanAttr], 0, 15);
            }
        }

        $traceIdentifiers = $identifiers;
        unset($traceIdentifiers[Terminal\Entity::VISA_MPAN]);
        unset($traceIdentifiers[Terminal\Entity::MC_MPAN]);
        unset($traceIdentifiers[Terminal\Entity::RUPAY_MPAN]);

        $this->trace->info(TraceCode::BHARAT_QR_CARD_IDENTIFIERS,
                           [
                               'qr_code'     => $qrCode->toArrayPublic(),
                               'identifiers' => $traceIdentifiers,
                           ]);

        return $identifiers;
    }

    protected function getUpiIdentifier(Entity $qrCode): array
    {
        // If UPI isn't enabled at all, we skip addition of UPI identifiers
        if ((new Merchant\Methods\Service())->isMethodEnabledForMerchant(Payment\Method::UPI, $qrCode->merchant) === false)
        {
            return [];
        }

        $terminal = (new VirtualAccount\Provider())->getTerminalForMethod(Payment\Method::UPI, $qrCode);

        if ($terminal !== null)
        {
            $vpa = $terminal->getVpa();
        }

        $identifier[Terminal\Entity::VPA] = $vpa ?? null;

        $this->trace->info(TraceCode::BHARAT_QR_UPI_IDENTIFIERS,
                           [
                               'qr_code'    => $qrCode->toArrayPublic(),
                               'identifier' => $identifier,
                           ]);

        return $identifier;
    }
}
