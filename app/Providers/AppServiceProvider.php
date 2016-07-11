<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerValidatorResolver();
    }

    protected function registerValidatorResolver()
    {
        $this->app['validator']->resolver(function($translator, $data, $rules, $messages, $customAttributes)
        {

            return new Razorpay\Spine\Validation\LaravelValidatorEx(
                $translator, $data, $rules, $messages, $customAttributes
            );
        });
    }

}
