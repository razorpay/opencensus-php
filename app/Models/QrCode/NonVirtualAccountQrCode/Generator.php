<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Models\QrCode;
use RZP\Models\Settings;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Gateway\Upi\Base;
use RZP\Models\BharatQr\Tags;
use RZP\Models\QrCode\Entity;
use RZP\Models\Payment\Gateway;
use Razorpay\Trace\Logger as Trace;
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

        $identifier[self::VPA] = $this->getVpaSetting(self::GATEWAY);

        $this->trace->info(TraceCode::BHARAT_QR_UPI_IDENTIFIERS,
                           [
                               'qr_code'    => $qrCode->toArrayPublic(),
                               'identifier' => $identifier,
                           ]);

        return $identifier;
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

        $vpa = $this->getVpaSetting(self::GATEWAY);

        return $this->generateUpiQrIntentUrl($vpa, $qrCode);
    }

    private function generateUpiQrIntentUrl($vpa, $qrCode)
    {
        $content = [
            Base\IntentParams::PAYEE_ADDRESS => $vpa,
            Base\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '', $this->merchant->getFilteredDba()),
            Base\IntentParams::TXN_REF_ID    => self::TR_PREFIX . $qrCode->getId() . QrCode\Constants::QR_CODE_V2_TR_SUFFIX,
            Base\IntentParams::TXN_NOTE      => 'Payment to ' . $this->merchant->getFilteredDba(),
            Base\IntentParams::TXN_CURRENCY  => 'INR',
            Base\IntentParams::MCC           => $this->merchant->getCategory(),
        ];

        if ($qrCode->hasFixedAmount())
        {
            $content[Base\IntentParams::TXN_AMOUNT] = $qrCode->getAmount() / 100;
        }

        return $url = 'upi://pay?' . str_replace(' ', '', urldecode(http_build_query($content)));
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
        $merchantIdentifier = str_replace(' ', '', $this->merchant->getBillingLabel());

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
}
