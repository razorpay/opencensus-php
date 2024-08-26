<?php

namespace RZP\Models\QrGatewayModule;

use App;
use Cache;

use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\Mode;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\QrCode\Entity as QrCodeEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;

/**
 * This class is to be used by all QR Code and QR Payment related operations to communicate with Mozart.
 */
class QrGatewayModule
{
    const QR_GATEWAY_CACHE_PREFIX = 'qr_gateway_';
    const QR_EXISTING_GATEWAY_CACHE_PREFIX = 'qr_existing_gateway_';

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        if (isset($this->app['rzp.mode'])) {
            $this->mode = $this->app['rzp.mode'];
        }
        else
        {
            $this->mode = Mode::LIVE;
            $this->app['rzp.mode'] = Mode::LIVE;
        }
    }

    public function generateIntentQr(QrCodeEntity $qrCode, TerminalEntity $terminal): array
    {
        $merchant = $qrCode->merchant;

        $input = [
            EntityConstants::QR_CODE         => $qrCode->toArray(),
            EntityConstants::TERMINAL        => $terminal->toArrayWithPassword(),
            EntityConstants::MERCHANT        => $merchant->toArray(),
            EntityConstants::MERCHANT_DETAIL => $merchant->merchantDetail->toArray(),
        ];

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::PAYMENTS,
            gateway    : $terminal->getGateway(),
            action     : Action::INTENT_QR,
            input      : $input,
            addEntities: false
        );

        return $response['data'];
    }

    public function generateIntentQrForUpiRzpApb(QrCodeEntity $qrCode, TerminalEntity $terminal, string $upiMode): array
    {
        $merchant = $qrCode->merchant;
        $notes = $qrCode->getNotes();

        $input = [
            EntityConstants::PAYMENT  => [
                'id'       => $qrCode->getId() . 'qrv2',
                'amount'   => $qrCode->getAmount(),
                'currency' => 'INR',
            ],
            EntityConstants::TERMINAL => $terminal->toArray(),
            EntityConstants::MERCHANT => $merchant->toArray(),
            'metadata'                => [
                'flow'   => 'intent',
                'remark' => 'Payment To ' . $merchant->getFilteredDba(),
            ],
            EntityConstants::UPI      => [
                'merchant_reference' => $qrCode->getId() . 'qrv2',
                'mode' => $upiMode,
            ],
        ];

        if (empty($notes['payment_context'] === false))
        {
            $input['metadata']['payment_context'] = strtoupper($notes['payment_context']);
        }

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::UPI_PAYMENTS,
            gateway    : $terminal->getGateway(),
            action     : Action::PAY_INIT,
            input      : $input,
            addEntities: false
        );

        $response['data'][EntityConstants::QR_CODE][QrCodeEntity::REFERENCE] =
            $response['data'][EntityConstants::UPI][\RZP\Gateway\Upi\Base\Entity::MERCHANT_REFERENCE];

        $response['data'][EntityConstants::QR_CODE][QrCodeEntity::QR_STRING] =
            $response['next']['intent_url'];

        return $response['data'];
    }

    public function checkQrPaymentStatus(QrCodeEntity $qrCode, TerminalEntity $terminal): array
    {
        $merchant = $qrCode->merchant;

        $input = [
            EntityConstants::QR_CODE         => $qrCode->toArray(),
            EntityConstants::TERMINAL        => $terminal->toArrayWithPassword(),
            EntityConstants::MERCHANT        => $merchant->toArray(),
            EntityConstants::MERCHANT_DETAIL => $merchant->merchantDetail->toArray(),
        ];

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::PAYMENTS,
            gateway    : $terminal->getGateway(),
            action     : Action::VERIFY_QR,
            input      : $input,
            addEntities: false
        );

        return $response['data'];
    }

    public function preProcessQrCallback($callbackData, string $gateway): array
    {
        $payload = json_encode($callbackData, JSON_THROW_ON_ERROR);

        $input = [
            'payload' => $payload,
            'gateway' => $gateway,
        ];

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::PAYMENTS,
            gateway    : $gateway,
            action     : Action::QR_PRE_PROCESS,
            input      : $input,
            addEntities: false
        );

        return $response['data'];
    }

    public static function checkIfNewQrPaymentGateway(string $gateway, $mode = Mode::LIVE)
    {
        // Fetch the variant from cache
        $qrVariant = Cache::get(self::QR_GATEWAY_CACHE_PREFIX . $gateway);

        // If there is no entry in cache
        if (empty($qrVariant) === true)
        {
            $app = App::getFacadeRoot();

            // Fetch the variant value from the experiment
            $qrVariant = $app['razorx']->getTreatment(
                $gateway,
                RazorxTreatment::QR_PAYMENT_REFACTOR_GATEWAY,
                $mode
            );

            // Set the cache for a TTL of 3 mins
            Cache::set(self::QR_GATEWAY_CACHE_PREFIX . $gateway, strtolower($qrVariant), 180);
        }

        return (strtolower($qrVariant) === RazorxTreatment::RAZORX_VARIANT_ON);
    }

    public static function checkIfOldGatewayProcessedThroughNewQrPaymentProcessingFlow(string $gateway, $mode = Mode::LIVE)
    {
        // Fetch the variant from cache
        $qrVariant = Cache::get(self::QR_EXISTING_GATEWAY_CACHE_PREFIX . $gateway);

        // If there is no entry in cache
        if (empty($qrVariant) === true)
        {
            $app = App::getFacadeRoot();

            // Fetch the variant value from the experiment
            $qrVariant = $app['razorx']->getTreatment(
                $gateway,
                RazorxTreatment::QR_PAYMENT_REFACTOR_EXISTING_GATEWAY,
                $mode
            );

            // Set the cache for a TTL of 3 mins
            Cache::set(self::QR_EXISTING_GATEWAY_CACHE_PREFIX . $gateway, strtolower($qrVariant), 180);
        }

        return (strtolower($qrVariant) === RazorxTreatment::RAZORX_VARIANT_ON);
    }
}
