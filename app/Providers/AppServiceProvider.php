<?php

namespace App\Providers;

use Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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

    public function register()
    {
        ;
    }
}
