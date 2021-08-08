<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations\Types;
use App;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;
use Illuminate\Foundation\Application;
use RZP\Models\Merchant\AutoKyc\Escalations\Entity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Merchant\AutoKyc\Escalations\Constants;

abstract class BaseEscalationType
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public abstract function triggerEscalation($merchants, $merchantsGmvList, string $type, int $level);

    public function createEscalationsV1($merchants, string $type, int $level,$escalationMethod)
    {
        foreach ($merchants as $merchant)
        {
            try
            {
                $escalation = (new Entity)->build([
                                                      Entity::MERCHANT_ID       => $merchant->getId(),
                                                      Entity::ESCALATION_TYPE   => $type,
                                                      Entity::ESCALATION_METHOD => $escalationMethod,
                                                      Entity::ESCALATION_LEVEL  => $level,
                                                  ]);
                $this->repo->merchant_auto_kyc_escalations->saveOrFail($escalation);

                $this->app['trace']->info(TraceCode::SELF_SERVE_ESCALATION_SUCCESS, [
                    'type'        => $type,
                    'level'       => $level,
                    'merchant_id' => $merchant->getId()
                ]);
            }
            catch (\Exception $e)
            {
                $this->app['trace']->info(TraceCode::SELF_SERVE_ESCALATION_FAILURE, [
                    'type'        => $type,
                    'level'       => $level,
                    'reason'      => 'something went wrong while handling escalation',
                    'trace'       => $e->getMessage(),
                    'merchant_id' => $merchant->getId()
                ]);
            }

        }
    }
}
