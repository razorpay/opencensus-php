<?php

namespace RZP\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class KnifeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //@TODO shift consumers to @json directive and remove @html_attr
        Blade::directive('html_attr', function ($expression) {
            return "<?php echo escape_html_attribute($expression); ?>";
        });

        Blade::directive('json', static function ($expression) {
            return "<?php print_jssafe_json($expression); ?>";
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
