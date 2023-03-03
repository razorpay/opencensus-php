<?php

namespace RZP\Models\Pricing\Calculator\Tax;

use Cache;

use App;
use RZP\Constants\Country;
use RZP\Models\Base as BaseModel;
use RZP\Trace\TraceCode;

abstract class Base extends BaseModel\Core
{
    protected $entity;

    protected $amount;

    protected $taxComponents;

    public function __construct(BaseModel\PublicEntity $entity, $amount)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->env = $this->app['env'];

        $this->entity = $entity;

        $this->merchant = $entity->merchant;

        $this->amount = $amount;
    }

    public static function getTaxCalculator($entity, $amount)
    {
        try {
            $country = strtoupper($entity->merchant->getCountry());
        }
        catch (\Throwable $e)
        {
            app('trace')->info(TraceCode::MERCHANT_NOT_ASSOCIATED_WITH_ENTITY, [
                "entity_type" => ($entity !== null) ? get_class($entity) :"entity is null"
            ]);

            $country = strtoupper(Country::IN);
        }

        $className = __NAMESPACE__ . '\\' . $country . '\\' . 'Calculator';

        $driver = new $className($entity, $amount);

        return $driver;
    }

    abstract protected function calculateTax($fees);

}
