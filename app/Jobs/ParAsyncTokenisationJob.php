<?php

namespace RZP\Jobs;

use App;
use RZP\Models\Customer\Token;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Throwable;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Diag\EventCode;
use RZP\Models\Card;

class ParAsyncTokenisationJob extends Job
{
    protected const RETRY_INTERVAL = 300;

    protected const MAX_RETRY_ATTEMPT = 1;

    protected $queueConfigKey = 'par_migration';

    public $timeout = 300;

    protected $cardId;

    protected $cardNumber;

    protected const ASYNC_PAR_NO_RETRY_ERROR_CODES = [
        'BAD_REQUEST_CARD_INVALID',
        'BAD_REQUEST_CARD_NOT_ELIGIBLE',
    ];

    /**
     * @var Token\Core
     */
    protected $tokenCore;

    /**
     * @var Card\Core
     */
    protected $cardCore;

    public function __construct(string $mode, string $cardId, string $cardNumber = null)
    {
        parent::__construct($mode);

        $this->cardId = $cardId;

        $this->cardNumber = $cardNumber;
    }

    public function init(): void
    {
        parent::init();

        $this->tokenCore = new Token\Core();

        $this->cardCore = new Card\Core();
    }

    /**
     * Process queue request
     */
    public function handle(): void
    {
        parent::handle();

        $startTime = millitime();

        $card = $this->repoManager->card->getCardById($this->cardId);

        try
        {

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_FETCH_PAR_INITIATED, $card);

            $this->trace->info(TraceCode::PAR_ASYNC_TOKENISATION_JOB_REQUEST, [
                "card_id"                   => $this->cardId,
                'network'                   => strtolower($card->getNetwork()),
                'attempts'                  => $this->attempts()
            ]);

            if(($this->cardCore->checkIfFetchingParApplicable($card->getNetwork())) === false)
            {
                $this->traceFetchingParNotApplicable($card);
                $this->delete();
                return;
            }

            $cardInput = $this->tokenCore->buildCardInputForPar($this->cardNumber, $card);

            list($network, $data) = $this->tokenCore->fetchParValue($cardInput, true);

            $card_fingerprint_id = $data["fingerprint"];

            $card->setProviderReferenceId($card_fingerprint_id);

            $card->saveOrFail();

            $this->trace->info(TraceCode::PAR_ASYNC_TOKENISATION_JOB_SUCCESS, [
                'card_id'             => $this->cardId,
                'timeTaken'           => millitime() - $startTime,
                'network'             => $card->getNetwork(),
                'card_fingerprint_id' => $card_fingerprint_id,
                'response'            => $data
            ]);

            $this->triggerEvent(EventCode::ASYNC_TOKENISATION_FETCH_PAR_SUCCESS, $card);

            $this->delete();

            return;
        }
        catch (Throwable $e)
        {
            $this->trackFailedFetchParEvent($e, $card ?? new CardEntity());

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAR_ASYNC_TOKENISATION_JOB_ERROR,
                [
                    'timeTaken'        => millitime() - $startTime,
                    'network'          => $card->getNetwork()
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function triggerEvent(array $eventData, CardEntity $card, array $customProperties = []): void
    {
        $properties = [
            'card_id'                   => $this->cardId,
            'card_network'              => $card->getNetwork(),
            'card_issuer'               => $card->getIssuer(),
            'attempt'                   => $this->attempts(),
            'card_fingerprint'          => $card->getProviderReferenceId()
        ];

        $properties = array_merge($properties, $customProperties);

        app('diag')->trackTokenisationEvent($eventData, $properties);
    }

    protected function trackFailedFetchParEvent(Throwable $e, CardEntity $card): void
    {
        $error_details = [
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
        ];

        $properties = [
            'error_detail' => json_encode($error_details),
        ];

        $this->triggerEvent(EventCode::ASYNC_TOKENISATION_FETCH_PAR_FAILED, $card, $properties);
    }

    protected function checkRetry(Throwable $e): void
    {
        if (($this->attempts() > self::MAX_RETRY_ATTEMPT) or
            (in_array($e->getCode(), self::ASYNC_PAR_NO_RETRY_ERROR_CODES, true)))
        {
            $this->trace->error(TraceCode::PAR_ASYNC_TOKENISATION_JOB_FAILED, [
                'card_id'          => $this->cardId,
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    protected function traceFetchingParNotApplicable($card): void
    {
        $this->trace->info(TraceCode::FETCH_PAR_NOT_APPLICABLE, [
            "card_id"                   => $this->cardId,
            'network'                   => strtolower($card->getNetwork()),
        ]);

        $this->triggerEvent(EventCode::ASYNC_TOKENISATION_FETCH_PAR_NOT_APPLICABLE, $card);
    }
}
