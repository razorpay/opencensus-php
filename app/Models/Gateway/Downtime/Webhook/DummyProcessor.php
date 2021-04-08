<?php

namespace RZP\Models\Gateway\Downtime\Webhook;

use App;
use Config;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Gateway\Downtime;

/**
 * This exists only for use in tests.
 */
class DummyProcessor implements ProcessorInterface
{
    public  function process(array $input)
    {
        $app = App::getFacadeRoot();

        $app['rzp.mode'] = Mode::TEST;

        \Database\DefaultConnection::set(Mode::TEST);

        unset($input['signature']);

        return (new Downtime\Core)->create($input)->toArrayAdmin();
    }

    public function validate(array $input)
    {
        $secret = $this->getSecret();

        $actualSignature = $input['signature'] ?? '';

        unset($input['signature']);

        $expectedSignature = $this->generateHMAC($input, $secret);

        if (hash_equals($expectedSignature, $actualSignature) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Signature validation failure.',
                'signature',
                [
                    'expected' => $expectedSignature,
                    'actual'   => $actualSignature,
                ]);
        }
    }

    protected function generateHMAC(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Since dummy source is only expected to be used for testing and never
     * on prod, using dashboard secret for this purpose is fine. Tests have
     * access to dashboard secret, and dashboard secret on prod is hidden.
     */
    protected function getSecret(): string
    {
        return Config::get('applications.merchant_dashboard.secret');
    }
}
