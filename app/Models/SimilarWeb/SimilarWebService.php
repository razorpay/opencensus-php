<?php

namespace RZP\Models\SimilarWeb;

use App;
use RZP\Trace\TraceCode;
use Cache;
use Razorpay\Trace\Logger as Trace;

class SimilarWebService
{
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = app('trace');

        $this->config = config(Constants::APPLICATIONS_SIMILARWEB);

        $this->similarWebClient = (new SimilarWebClient());
    }

    public function fetchVisitsForDomain(SimilarWebRequest $request): ?int
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['applications.similarweb.mock'];

        if($mock === true)
        {
            return 2500;
        }

        if (empty($request->domain) == false) {
            $response = $this->similarWebClient->getDetails($request);
        } else {
            return 0;
        }

        if ($response->isSuccess()) {
            $this->trace->info(TraceCode::SIMILARWEB_RESPONSE_SUCCESS, [
                'domain'            => $request->domain,
                'website_visits'    => $response->getVisits()
                ]);
        }

        return $response->getVisits();
    }
}