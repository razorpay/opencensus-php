<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations;

use App;
use Illuminate\Foundation\Application;
use Razorpay\Trace\Logger as Trace;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\AutoKyc\Escalations\Types\Email;
use RZP\Models\Merchant\AutoKyc\Escalations\Types\Factory;
use RZP\Models\Merchant\AutoKyc\Escalations\Types\Workflow;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\AutoKyc\Escalations\Types\BaseEscalationType;

class Handler
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];
    }

    /**
     * Main method that handles escalation:
     * - Identifies which escalation method to use for trigger escalation
     * - Triggers escalation
     * - Saves entry in the database (merchant_auto_kyc_escalations)
     *
     * @param        $merchants
     * @param        $merchantsGmvList
     * @param string $type
     * @param int    $level
     */
    public function handleEscalations($merchants, $merchantsGmvList, string $type, int $level)
    {
        try
        {
            $instance = Factory::getInstance($type, $level);

            $instance->triggerEscalation($merchants, $merchantsGmvList, $type, $level);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SELF_SERVE_ESCALATION_FAILURE, [
                'type'      => $type,
                'level'     => $level,
                'reason'    => 'something went wrong while handling escalation',
                'trace'     => $e->getMessage()
            ]);
        }
    }
}
