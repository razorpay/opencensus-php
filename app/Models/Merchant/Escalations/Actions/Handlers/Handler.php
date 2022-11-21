<?php


namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

use App;
use Illuminate\Foundation\Application;
use RZP\Base\RepositoryManager;
use RZP\Models\Merchant\Escalations\Actions\Constants;
use RZP\Models\Merchant\Escalations\Actions\Entity;
use RZP\Trace\TraceCode;

abstract class Handler
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

    protected $trace;

    protected $mutex;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

        $this->mutex = $this->app['api.mutex'];
    }

    public abstract function execute(string $merchantId, Entity $action, array $params = []);

    public function handleAction(string $merchantId, Entity $action, array $params = [])
    {
        $status = Constants::PENDING;
        try
        {
            $this->execute($merchantId, $action, $params);
            $status = Constants::SUCCESS;
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ACTION_FAILURE, [
                'merchant_id'   => $merchantId,
                'action_id'     => $action->getId(),
                'escalation_id' => $action->getEscalationId(),
                'error'         => $e->getMessage(),
            ]);
            $status = Constants::FAILURE;
        }
        finally
        {
            $action->edit([
                Entity::STATUS  => $status
            ]);
            $this->repo->onboarding_escalation_actions->saveOrFail($action);
        }
    }
}
