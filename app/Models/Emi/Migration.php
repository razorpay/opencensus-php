<?php


namespace RZP\Models\Emi;

use App;
use Config;
use RZP\Exception;
use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;

class Migration
{
    const CREATE    = 'create';
    const DELETE    = 'delete';
    const FETCH     = 'fetch';
    const QUERY     = 'query';
    const EMI_QUERY = 'emi_query';
    const EMI_PLANS = 'emi_plans';

    const DURATIONS     = 'durations';
    const MERCHANT_IDS  = 'merchant_ids';

    protected $app;

    protected $trace;

    public function __construct()
    {
        $this->app  = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function isCpsFetchEnabled()
    {
        if(((bool) Admin\ConfigKey::get(Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED, false) == false) or
            ((bool) Admin\ConfigKey::get(Admin\ConfigKey::CARD_PAYMENT_SERVICE_EMI_FETCH, false) == false))
        {
            return false;
        }

        return true;
    }

    public function migrate($action, $plan)
    {
        if(((bool) Admin\ConfigKey::get(Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED, false) == false) or
            ((bool) Admin\ConfigKey::get(Admin\ConfigKey::CARD_PAYMENT_SERVICE_EMI_FETCH, false) == true))
        {
            $ex = new Exception\ServerErrorException('emi_plan sync call failed',
                ErrorCode::SERVER_ERROR_CARD_PAYMENT_SERVICE_EMI_PLAN_SYNC_ENABLED, [
                    'message' => 'Please disable emi sync admin config' ,
                ]);

            $this->trace->error(TraceCode::CARD_PAYMENT_SERVICE_EMI_INVALID_MIGRATION_ATTEMPT, [
                'error' => $ex->getMessage(),
                'message' => 'emi fetch from cps is enabled. Failed to migrate',
            ]);

            throw $ex;
        }

        return $this->migrationRequestHandler($action, $plan);
    }

    public function handleMigration($action, $emiPlan, $id = '', $input = [])
    {
        if ($this->isCpsFetchEnabled() == false)
        {
            return null;
        }

        return $this->migrationRequestHandler($action, $emiPlan, $id, $input);
    }

    function migrationRequestHandler($action, $emiPlan, $id = '', $input = [], $ignoreFailure = false)
    {
        $response = [];

        try {
            switch ($action) {
                case self::CREATE:

                    $response = $this->app['card.payments']->create(self::EMI_PLANS, $emiPlan->toArrayAdmin());
                    break;

                case self::DELETE:

                    $response = $this->app['card.payments']->delete(self::EMI_PLANS, ($emiPlan->getId() ?: $id));
                    break;

                case self::FETCH:

                    $response = $this->app['card.payments']->get(self::EMI_PLANS, $id);
                    break;

                case self::QUERY:

                    $response = $this->app['card.payments']->query(self::EMI_PLANS, $input);
                    break;

                case self::EMI_QUERY:

                    $response = $this->app['card.payments']->emiPlanQuery(self::EMI_PLANS, $input);
                    break;

            }

            if ((isset($response['success'])) and
                ($response['success'] == true))
            {
                unset($response['success']);

                return $response;
            }
        }
        catch(\Exception $ex)
        {
            $this->trace->error(TraceCode::CARD_PAYMENT_SERVICE_EMI_REQUEST_RESPONSE_ERROR, [
                'error' => $ex->getMessage(),
                'message' => 'emi sync request response error',
            ]);
        }

        if ($ignoreFailure === true)
        {
            return [];
        }

        $this->trace->warning(TraceCode::CARD_PAYMENT_SERVICE_EMI_FETCH_DISABLING, [
            'config_key' => Admin\ConfigKey::CARD_PAYMENT_SERVICE_EMI_FETCH,
            'message' => 'emi fetch disabled',
        ]);

        if ((empty($this->app['rzp.mode']) === true) or
            ((empty($this->app['rzp.mode']) !== true) and ($this->app['rzp.mode'] !== 'test')))
        {
            (new Admin\Service)->setConfigKeys(
                [Admin\ConfigKey::CARD_PAYMENT_SERVICE_EMI_FETCH => false]
            );

            $this->app['slack']->queue(
                TraceCode::CARD_PAYMENT_SERVICE_EMI_FETCH_DISABLING,
                [
                    'message' => 'emi fetch disabled',
                    'priority' => 'P2',
                ],
                [
                    'channel' => Config::get('slack.channels.card_payments_alert'),
                ]);
        }

        return null;

//        throw new Exception\ServerErrorException('emi_plan sync call failed',
//            ErrorCode::SERVER_ERROR_CARD_PAYMENT_SERVICE_EMI_PLAN_SYNC_CALL_FAILED, [
//                'id' => ($emiPlan == null ) ? $emiPlan->getId() : $id ,
//                'merchant'  => ($emiPlan == null ) ? $emiPlan->getMerchantId() : '' ,
//            ]);
    }

    public function getPlansFromCps($input)
    {
        $this->handleMigration(self::QUERY, null, '', $input);
    }

    //Returns a collection of eloquent model instances.
    public function getEntityList($input)
    {
        if ($input === null)
        {
            return new PublicCollection;
        }

        $entityList = [];

        foreach($input as $data)
        {
            $entityData = $this->getEntity($data);

            array_push($entityList, $entityData);
        }

        return new PublicCollection($entityList);
    }

    //Returns an eloquent model instance based on the given input.
    public function getEntity($input)
    {
        unset($input['created_at']);
        unset($input['updated_at']);
        unset($input['deleted_at']);

        $id = ($input['id']);
        unset($input['id']);

        $entity = (new Entity)->build($input);
        $entity->setId($id);
        $entity->setExternal(true);

        return $entity;
    }

    //Get list of durations from all the fetched emi plans.
    public function getDurationsArray($data)
    {
        $durations = [];

        foreach($data as $plan)
        {
            try
            {
                array_push($durations, $plan[Entity::DURATION]);
            }
            catch (\Exception $ex)
            {
                $this->trace->error(TraceCode::CARD_PAYMENT_SERVICE_EMI_RESPONSE_DURATION_MISSING, [
                    'id'        => $plan[Entity::ID] ?: '',
                    'error'     => $ex->getMessage(),
                    'message'   => 'duration must be present in the emi plan',
                ]);

                throw $ex;
            }
        }

        return $durations;
    }
}
