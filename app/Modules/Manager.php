<?php

namespace RZP\Modules;

use Illuminate\Support\Manager as SupportManager;

use RZP\Exception\LogicException;

class Manager extends SupportManager
{
    public function getDefaultDriver()
    {
        throw new LogicException('No default module driver is specified');
    }

    public function __get(string $module)
    {
        return $this->driver($module);
    }

    protected function createSubscriptionDriver()
    {
        $driverFactoryClass = __NAMESPACE__ . '\\' . 'Subscriptions' . '\\' . 'Factory';

        return $driverFactoryClass::get();
    }

    protected function createSecondFactorAuthDriver()
    {
        $driverFactoryClass = __NAMESPACE__ . '\\' . 'SecondFactorAuth' . '\\' . 'Factory';

        return $driverFactoryClass;
    }
}
