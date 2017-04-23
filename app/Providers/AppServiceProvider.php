<?php

namespace App\Providers;

use App;
use Auth;
use Blade;
use Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @see http://symfony.com/blog/security-releases-symfony-2-0-24-2-1-12-2-2-5-and-2-3-3-released
     *
     * We return a 403 error if the Host is not present in this list.
     */
    const TRUSTED_HOSTS = [
        '.*\.?razorpay.com$',
    ];

    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupBlade();

        $this->registerValidatorResolver();

        $this->registerCustomAuthProvider();

        $this->registerTrustedHosts();
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

    protected function registerTrustedHosts()
    {
        if (App::environment('production', 'beta'))
        {
            Request::setTrustedHosts(self::TRUSTED_HOSTS);
        }
    }

    public function register()
    {
        ;
    }
}
