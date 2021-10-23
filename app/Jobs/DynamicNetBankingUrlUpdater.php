<?php

namespace RZP\Jobs;

use App;
use RZP\Http\Request\Requests;
use Illuminate\Support\Facades\Redis;

use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;
use RZP\Gateway\Netbanking;

class DynamicNetBankingUrlUpdater extends Job
{
    const USERNAME  = 'Username';

    const API       = 'API';

    const TEST_ID   = 'TestID';

    const BANK_URL  = 'WebsiteURL';

    const TEST_TAGS = 'TestTags';

    const ISSUER    = 'issuer';

    const METHOD    = 'method';

    const SUCCESS   = 'Success';

    const STATUS    = 'Status';

    const ISSUES    = 'Issues';

    protected $app = null;

    protected $redis = null;

    protected $username = null;

    protected $apiKey = null;

    protected $statusCakeUrl = null;

    protected $statusCakeUpdateUrl = null;

    public function __construct(string $mode = null)
    {
        parent::__construct($mode);

        $this->app = App::getFacadeRoot();

        $this->redis = $this->app['redis']->connection('mutex_redis');

        list($this->username, $this->apiKey, $this->statusCakeUrl, $this->statusCakeUpdateUrl) =
            $this->fetchStatusCakeCredentials();
    }

    public function handle()
    {
        parent::handle();

        $requestHeaders = $this->getRequestHeaders();

        $responseArray = $this->makeRequestAndGetData($this->statusCakeUrl, $requestHeaders);

        foreach ($responseArray as $testArray) {

            if(isset($testArray[self::TEST_ID]) === false)
            {
                continue;
            }

            $testId = $testArray[self::TEST_ID];

            $bankUrl = $testArray[self::BANK_URL];

            $tagsArray = $this->getTagsArray($testArray[self::TEST_TAGS]);

            $issuer = $tagsArray[self::ISSUER];

            $urlFromCache = $this->getUrlFromCache($issuer);

            if (empty($urlFromCache) === false and $urlFromCache !== $bankUrl)
            {
                $this->updateUrlInStatusCake($testId, $urlFromCache);
            }
        }
    }

    protected function fetchStatusCakeCredentials()
    {
        $uname = $this->app['config']->get('applications.gateway_downtime.statuscake.username');

        $apiKey = $this->app['config']->get('applications.gateway_downtime.statuscake.api_key');

        $testsUrl = $this->app['config']->get('applications.gateway_downtime.statuscake.tests_url');

        $updateUrl = $this->app['config']->get('applications.gateway_downtime.statuscake.update_url');

        return [$uname, $apiKey, $testsUrl, $updateUrl];
    }

    protected function getRequestHeaders()
    {
        return [self::USERNAME => $this->username, self::API => $this->apiKey];
    }

    protected function getResponseData($response)
    {
        $array = Utility::jsonToArray($response->body);

        if (empty($array) === true)
        {
            return [];
        }

        return $array;
    }

    protected function getTagsArray($tags)
    {
        $tagsArray = [];

        foreach ($tags as $tag) {

            list($fieldName, $fieldValue) = explode(':', $tag);

            $tagsArray[$fieldName] = $fieldValue;
        }

        return $tagsArray;
    }

    protected function makeRequestAndGetData($url, $requestHeaders, $content = [], $method = 'POST')
    {
        try
        {
            $response = Requests::request(
                $url,
                $requestHeaders,
                $content,
                $method);
        }
        catch (\Throwable $exc)
        {
            $data = [
                'exception'     => $exc->getMessage(),
                'url'           => $url,
                'input'         => $content,
            ];

            $this->trace->error(TraceCode::STATUSCAKE_CONNECTION_FAILED, $data);

            return [];
        }

        $responseArray = $this->getResponseData($response);

        return $responseArray;
    }

    protected function getCacheKey($bank)
    {
        return Netbanking\Base\Gateway::getNetbankingUrlCacheKey($bank);
    }

    protected function getUrlFromCache($issuer)
    {
        $cacheKey = $this->getCacheKey($issuer);

        try
        {
            $value = $this->redis->get($cacheKey);
        }
        catch (\Throwable $exc)
        {
            $this->trace->error(TraceCode::NETBANKING_URL_CACHE_MISS, [$issuer]);

            return;
        }

        if (empty($value) === true)
        {
            $this->trace->info(TraceCode::NETBANKING_URL_CACHE_MISS, [$issuer]);
        }

        return $value;
    }

    protected function updateUrlInStatusCake($testId, $newUrl)
    {
        $requestHeaders = $this->getRequestHeaders();

        $updateData = $this->getRequestDataForUpdate($testId, $newUrl);

        try
        {
            $responseArray = $this->makeRequestAndGetData($this->statusCakeUpdateUrl, $requestHeaders, $updateData,
                'PUT');
        }
        catch (\Throwable $exc)
        {
            $this->trace->info(TraceCode::STATUSCAKE_RETURNED_FAILURE, $updateData);

            return;
        }

        if (empty($responseArray) === true)
        {
            return;
        }

        if ($responseArray[self::SUCCESS] !== true)
        {
            $this->trace->info(TraceCode::STATUSCAKE_RETURNED_FAILURE, [$responseArray[self::ISSUES]]);
        }
    }

    protected function getRequestDataForUpdate($testId, $newUrl)
    {
        $data = [
            self::TEST_ID   => $testId,
            self::BANK_URL  => $newUrl
        ];

        return $data;
    }
}
