<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Services\Workflow;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Metric;
use RZP\Models\Merchant\Constants;
use Razorpay\Trace\Logger as Trace;
use Jitendra\Lqext\TransactionAware;
use RZP\Models\Merchant\Balance\Type;



class SubMerchantSupportEntitiesCreateJob extends Job
{
    use TransactionAware;

    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 3;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $merchantId;

    protected $partnerId;

    public $timeout = 120;

    /**
     * @var array
     */
    private $input;


    public function __construct($mode, array $input)
    {
        parent::__construct($mode);
        $this->merchantId = $input['merchant_id'];
        $this->partnerId  = $input['partner_id'];
        $this->input = $input;
    }

    public function handle()
    {
        parent::handle();

        $this->resetWorkflowSingleton();

        $this->trace->info(
            TraceCode::SUBMERCHANT_SUPPORTING_ENTITIES_ASYNC_JOB_REQUEST,
            [
                'merchant_id' => $this->merchantId,
                'partner_id'  => $this->partnerId,
                'input'       => $this->input
            ]
        );

        try
        {
            $merchant = $this->repoManager->merchant->findOrFailPublic($this->merchantId);
            $partner  = $this->repoManager->merchant->findOrFailPublic($this->partnerId);

            $merchantBalance = $this->repoManager->balance->getMerchantBalanceByType($merchant->getId(), Type::PRIMARY, $this->mode);
            // If merchant balance is already created, assuming that this job has already run once without any errors.
            // To make this job idempotent, we are relying on this entity to be more safer during executing duplicate messages in very corner cases
            if ($merchantBalance !== null)
            {
                $this->trace->info(TraceCode:: SUBMERCHANT_SUPPORTING_ENTITIES_ASYNC_JOB_ALREADY_EXECUTED,
                                   [
                                       'merchant_id' => $this->merchantId
                                   ]);
            }
            else
            {
                $mutexKey = $this->merchantId . '_subm_entities_creation';
                $this->mutex->acquireAndRelease(
                    $mutexKey,
                    function() use ($merchant, $partner) {
                        $this->repoManager->transactionOnLiveAndTest(
                            function() use ($merchant, $partner) {
                                $merchantCore = (new Merchant\Core());
                                $merchantCore->addMerchantSupportingEntitiesAsync($merchant, $partner);
                                $merchantCore->associateLegalEntityToSubmerchant($merchant, $this->input);
                                $merchantCore->addToDefaultUnClaimedGroup($merchant);
                                $merchantCore->syncHeimdallRelatedEntities($merchant, $this->input);
                                $merchantCore->setSubMerchantMaxPaymentAmount($partner, $merchant, $this->input[Detail\Entity::BUSINESS_TYPE]);
                                $merchantCore->assignSubMerchantPricingPlan($partner, $merchant, $this->input['linked_account']);
                                $this->repoManager->saveOrFail($merchant);
                            }
                        );
                    },
                    Constants::SUBM_CREATE_ENTITIES_LOCK_TIME_OUT,
                    ErrorCode::BAD_REQUEST_SUBM_CREATE_ENTITIES_IN_PROGRESS
                );

                $this->trace->info(
                    TraceCode::SUBMERCHANT_SUPPORTING_ENTITIES_ASYNC_JOB_SUCCESS,
                    [
                        'merchant_id' => $this->merchantId,
                        'partner_id'  => $this->partnerId
                    ]
                );

            }

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SUBMERCHANT_SUPPORTING_ENTITIES_ASYNC_JOB_FAILED,
                [
                    'merchant_id' => $this->merchantId,
                    'partner_id'  => $this->partnerId,
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::SUBMERCHANT_SUPPORTING_ENTITIES_ASYNC_JOB_MESSAGE_DELETE, [
                'merchant_id'  => $this->merchantId,
                'partner_id'   => $this->partnerId,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->trace->count(Metric::SUBMERCHANT_SUPPORT_ENTITIES_CREATION_FAILURE_TOTAL, []);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

    private function resetWorkflowSingleton()
    {
        $app             = App::getFacadeRoot();
        $app['workflow'] = new Workflow\Service($app);
    }
}
