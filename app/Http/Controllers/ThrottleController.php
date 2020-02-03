<?php

namespace RZP\Http\Controllers;

use Illuminate\Support\Facades\Redis;

use ApiResponse;
use RZP\Models\Key;
use RZP\Trace\TraceCode;
use RZP\Services\Throttle;
use RZP\Base\RuntimeManager;
use RZP\Http\Throttle\Constant;

class ThrottleController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Throttle\Service::class;

    /**
     * Refer Key\Core's writeCache() method.
     */
    public function bootstrapKeyCache()
    {
        RuntimeManager::setMemoryLimit('256M');
        RuntimeManager::setTimeLimit(1000);

        $totalIteration = 0;
        $failedIteration = 0;
        $chunkSize = 1000;

        Key\Entity::select('id', 'merchant_id')
            ->orderBy(Key\Entity::ID)
            ->chunk($chunkSize, function($keys) use (&$totalIteration, &$failedIteration) {
                ++$totalIteration;
                $args = [];
                foreach ($keys as $key)
                {
                    $args[] = Constant::KEYID_MID_KEY_PREFIX . $key->getPublicId();
                    $args[] = $key->getMerchantId();
                }

                try
                {
                    Redis::connection()->client()->mset(...$args);
                }
                catch (\Throwable $e)
                {
                    ++$failedIteration;
                    $this->trace->traceException($e, null, null, compact($args));
                }
            });

        $this->trace->info(TraceCode::BOOTSTRAP_KEY_CACHE_SUMMARY, compact('totalIteration', 'failedIteration'));

        return ApiResponse::json([]);
    }
}
