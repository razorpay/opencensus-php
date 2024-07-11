<?php

namespace RZP\Models\QrGatewayModule;

use RZP\Constants\Entity as EntityConstants;
use RZP\Models\QrCode\Entity as QrCodeEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;

/**
 * This class is to be used by all QR Code and QR Payment related operations to communicate with Mozart.
 */
class QrGatewayModule
{
    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config'];

        if (isset($this->app['rzp.mode'])) {
            $this->mode = $this->app['rzp.mode'];
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
            namespace  : Namespaces::QR_CODES,
            gateway    : $terminal->getGateway(),
            action     : Action::INTENT_QR,
            input      : $input,
            addEntities: false
        );

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
            namespace  : Namespaces::QR_CODES,
            gateway    : $terminal->getGateway(),
            action     : Action::QR_STATUS_CHECK,
            input      : $input,
            addEntities: false
        );

        return $response['data'];
    }

    public function preProcessQrCallback(array $callbackData, string $gateway): array
    {
        $input = [
            'gateway' => [
                'payload' => $callbackData,
                'gateway' => $gateway,
            ],
        ];

        $response = $this->app['mozart']->sendMozartRequest(
            namespace  : Namespaces::QR_CODES,
            gateway    : $gateway,
            action     : Action::QR_PRE_PROCESS,
            input      : $input,
            addEntities: false
        );

        return $response['data'];
    }
}
