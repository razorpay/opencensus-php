<?php

use Mailgun\Mailgun;

class RoutesTest extends Tests\TestCase
{
    public function setUp()
    {
        parent::setUp();

        //
        // Setting up db
        //
        Artisan::call('migrate');

        //
        // Enable filters
        //
        Route::enableFilters();
    }

    public function testJSONPRoute()
    {
        ;
    }

    public function testMailgunRoute()
    {
        $routes = array(
            'hdfc_mpr_production_test',
            // 'hdfc_mpr_production_live',
            // 'hdfc_mpr_beta_test',
            // 'hdfc_mpr_beta_live'
        );

        $mgConfig = \Config::get('applications.mailgun');

        if ($mgConfig['mock'])
            $this->markTestSkipped('Can only run this test when mailgun is not mocked');

        $mg = new Mailgun($mgConfig['key']);

        $result = $mg->get('routes')->http_response_body;

        $count = $result->total_count;

        foreach ($routes as $route)
        {
            $this->matchRouteWithMailgunRoutes($route, $result);
        }
    }

    protected function matchRouteWithMailgunRoutes($route, $result)
    {
        $urls = \Config::get('url');
        $mg = \Config::get('applications.mailgun');
        $secret = $mg['secret'];

        $match = false;
        $repeat = false;

        $tokens = explode('_', $route);
        $mode = $tokens[3];
        $env = $tokens[2];

        $apiKey = 'rzp_'.$mode;
        $basicAuth = $apiKey . ':' . $secret;
        $url = $urls[$env];

        $https = strstr($url, 'https://');
        $scheme = ($https === false) ? 'http://' : 'https://';
        $host = substr($url, strlen($scheme));

        $action = "forward('".$scheme . $basicAuth . '@' . $host . "/v1/gateway/mpr/reconcile')";
        $expression = "match_recipient('". $route . '@' . $mg['url']."')";

        foreach ($result->items as $item)
        {
            if (strcmp($item->expression, $expression) === 0)
            {
                foreach ($item->actions as $itemAction)
                {
                    if (strcmp($itemAction, $action) === 0)
                    {
                        if ($match === true)
                        {
                            $repeat = true;
                            break;
                        }

                        $match = true;
                    }
                }
            }
        }

        $this->assertEquals($match, true, 'No rule found to forward hdfc mpr emails to api');
        $this->assertEquals($repeat, false, 'Multiple rules forwarding to mpr email address');
    }
}
