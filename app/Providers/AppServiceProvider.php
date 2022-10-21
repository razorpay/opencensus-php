<?php

namespace App\Providers;

use App;
use Auth;
use Blade;
use Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Artisan::call('config:clear');

        $this->setupBlade();

        $this->registerValidatorResolver();

        $this->registerCustomAuthProvider();

        $this->registerCustomDashboardUserProvider();
    }

    protected function setupBlade()
    {
        setlocale(LC_MONETARY, 'en_IN');
        Blade::directive('format_money', function($expression) {
            return "<?php echo money_format('%!i', (with($expression)/100)); ?>";
        });
    }


    protected function registerValidatorResolver()
    {
        $this->app['validator']->resolver(function($translator, $data, $rules, $messages, $customAttributes)
        {
            return new \Razorpay\Spine\Validation\LaravelValidatorEx(
                $translator, $data, $rules, $messages, $customAttributes
            );
        });
    }

    protected function registerCustomAuthProvider()
    {
        Auth::provider('api', function($app, $config)
        {
            return new ApiUserProvider($app, $config);
        });

        Auth::extend('api', function($app, $name, array $config)
        {
            return new ApiGuard(Auth::createUserProvider($config['provider']), $app);
        });
    }

    protected function registerCustomDashboardUserProvider()
    {
        Auth::provider('api_user', function($app, $config)
        {
            return new DashboardUserProvider($app, $config);
        });
    }

    public function register()
    {
        ;
    }
}
