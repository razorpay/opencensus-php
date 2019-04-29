<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Service as PaymentService;

class CardVaultMigrationJob extends Job
{
    const RELEASE_WAIT_SECS    = 300;

    /**
     * @var string
     */
    protected $queueConfigKey = 'carvault_migration';

    /**
     * @var array
     */
    protected $input;


    public function __construct(array $input)
    {
        parent::__construct($input['mode']);

        $this->input = $input;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        try
        {
            parent::handle();

            $cardId = null;
            $paymentId = null;

            $this->trace->info(
                TraceCode::VAULT_TOKEN_MIGRATION_REQUEST,
                [
                    'input'       => $this->input,
                ]
            );

            if (empty($this->input['card_id']) === true)
            {
                $this->delete();

                return;
            }

            $cardId = $this->input['card_id'];

            if (empty($this->input['payment_id']) === false)
            {
                $paymentId = $this->input['payment_id'];
            }

            $updated = (new PaymentService)->migrateCardVaultToken($cardId, $paymentId);

            $this->trace->info(
                TraceCode::VAULT_TOKEN_MIGRATION_SUCCESSFULL,
                ['token_migrated' => $updated]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::VAULT_TOKEN_MIGRATION_ERROR,
                $this->input);
        }
    }
}
