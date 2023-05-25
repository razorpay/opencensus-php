<?php

namespace RZP\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RZP\Base\RepositoryManager;
use RZP\Constants\Mode;
use RZP\DTO\AffordabilityServiceConfig;
use RZP\Models\Base\AffordabilityObserver;
use RZP\Models\Emi\Entity as EmiEntity;
use RZP\Models\Offer\Entity as OfferEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Merchant\Methods\Entity as MethodsEntity;
use RZP\Services\AffordabilityService;
use RZP\Services\Mock\AffordabilityService as AffordabilityServiceMock;

class AffordabilityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerAffordabilityService();
        $this->registerAffordabilityObserver();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        EmiEntity::observe(AffordabilityObserver::class);
        OfferEntity::observe(AffordabilityObserver::class);
        TerminalEntity::observe(AffordabilityObserver::class);
        MethodsEntity::observe(AffordabilityObserver::class);
    }

    /**
     * Register AffordabilityService class which helps communicate with checkout-affordability-api.
     */
    protected function registerAffordabilityService(): void
    {
        $this->app->singleton(AffordabilityService::class, static function (Application $app) {
            $appConfig = $app->make('config');

            $useMock = $appConfig->get('applications.affordability.mock');

            $config = new AffordabilityServiceConfig([
                'baseUrl' => $appConfig->get('applications.affordability.url'),
                'mode' => $app['rzp.mode'] ?? ($app->runningUnitTests() ? Mode::TEST : Mode::LIVE),
                'secret' => $appConfig->get(
                    'applications.affordability.service_secret'
                ),
            ]);

            $logger = $app->make('trace');

            if ($useMock === true) {
                return new AffordabilityServiceMock($logger, $config);
            }

            return new AffordabilityService($logger, $config);
        });
    }

    /**
     * Register AffordabilityObserver class which listens to events on entities
     * which are a part of Razorpay's affordability suite.
     */
    protected function registerAffordabilityObserver(): void
    {
        $this->app->bind(AffordabilityObserver::class, static function(Application $app) {
            $service = $app->make(AffordabilityService::class);

            /** @var RepositoryManager $repo */
            $repo = $app->make('repo');

            return new AffordabilityObserver($service, $repo->feature, $repo->key);
        });
    }
}
