<?php

namespace RZP\Models\PaymentLink\NocodeCustomUrl;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Models\PaymentLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Once all slugs are migrated this class would not be used at all
 */
class DataMigrator
{
    /**
     * @var mixed|object
     */
    private $app;

    /**
     * @var \Illuminate\Cache\Repository
     */
    private $cache;

    /**
     * @var \Razorpay\Trace\Logger
     */
    private $trace;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var int
     */
    private $cacheTTL;

    /**
     * @var \RZP\Models\PaymentLink\Repository
     */
    private $plRepo;

    /**
     * @var \RZP\Models\PaymentLink\ElfinWrapper
     */
    private $elfin;

    /**
     * @var \Illuminate\Config\Repository
     */
    private $config;

    /**
     * @var \RZP\Models\PaymentLink\NocodeCustomUrl\Core
     */
    private $core;

    /**
     * @var string
     */
    private $viewType;

    /**
     * @var int
     */
    private $lastProcessedCreatedAt;

    const CACHE_TAG = ":NOCODE:CUSTOM:URL:MIGRATION:LAST_PROCESSED_CREATED_AT";

    /**
     * @param        $batchSize
     * @param        $cacheTTL
     * @param string $viewType
     */
    public function __construct($batchSize = 500, $cacheTTL = null, string $viewType = PaymentLink\ViewType::PAGE)
    {
        $this->batchSize = $batchSize;

        $this->cacheTTL = $cacheTTL ?? $this->getDefaultTTL();

        $this->viewType = $viewType;

        $this->app = App::getFacadeRoot();

        $this->cache = $this->app['cache'];

        $this->trace = $this->app['trace'];

        $this->config = $this->app['config'];

        $this->plRepo = new PaymentLink\Repository();

        $this->elfin = new PaymentLink\ElfinWrapper(ElfinService::GIMLI);

        $this->core = new PaymentLink\NocodeCustomUrl\Core();

        $this->lastProcessedCreatedAt = null;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_MIGRATION_START);

        $this->setLastProcessedCreatedAtFromCache();

        DB::connection(Mode::LIVE)
            ->table(Table::PAYMENT_LINK)
            ->where(PaymentLink\Entity::VIEW_TYPE, $this->viewType)
            ->when(empty($this->lastProcessedCreatedAt) === false, function ($query) {
                return $query->where(PaymentLink\Entity::CREATED_AT, '>', $this->lastProcessedCreatedAt);
            })
            ->select([PaymentLink\Entity::ID, PaymentLink\Entity::CREATED_AT])
            ->orderBy(PaymentLink\Entity::CREATED_AT)
            ->chunk($this->batchSize, function ($items) {
                try {
                    $this->runEachRow($items->toArray());

                    $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_MIGRATION_COMPLETED, []);
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException($e);

                    $this->trace->error(TraceCode::NOCODE_CUSTOM_URL_MIGRATION_FAILED, []);
                }
            });
    }

    /**
     * @param array $rows
     *
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function runEachRow(array $rows)
    {
        if (empty($rows) === true)
        {
            throw new BadRequestValidationFailureException('rows are required');
        }

        $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_MIGRATION_IDS_START);

        foreach ($rows as $row)
        {
            try
            {
                $this->processById($row->id);

                $this->cacheLastProcessedCreatedAt($row->created_at);
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException($exception);

                $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_MIGRATION_ID_PROCESSEING_FAILED, ['id' => $row->id]);
            }
        }
    }

    /**
     * Default cache ttl is for 1 Month
     *
     * @return int
     */
    private function getDefaultTTL()
    {
        return 60 * 60 * 24 * 30;
    }

    /**
     * @param string $id
     *
     * @return void
     * @throws \Exception
     */
    private function processById(string $id)
    {
        $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_MIGRATION_ID_PROCESSEING, ['id' => $id]);

        $paymentPage = $this->plRepo->find($id);

        $slug = $paymentPage->getSlugFromShortUrl();

        $data = $this->elfin->expand($slug);

        $this->upsertFromGimliResponse($data, $paymentPage);

        $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_MIGRATION_ID_PROCESSEING_DONE, ['id' => $id]);
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $paymentPage
     * @param array                          $alias
     *
     * @return void
     * @throws \Exception
     */
    private function upsert(PaymentLink\Entity $paymentPage, array $alias): void
    {
        $this->plRepo->transaction(function () use ($paymentPage, $alias) {
            $this->plRepo->lockForUpdateAndReload($paymentPage);

            [$url, $params, $fail] = (new PaymentLink\Core())->getShortenUrlRequestParams($paymentPage, $alias['hash']);

            $this->core->upsert([
                PaymentLink\NocodeCustomUrl\Entity::SLUG        => $alias['hash'],
                PaymentLink\NocodeCustomUrl\Entity::META_DATA   => $params['metadata'],
                PaymentLink\NocodeCustomUrl\Entity::DOMAIN      => Entity::determineDomainFromUrl($url),
            ], $paymentPage->merchant, $paymentPage);
        });
    }

    /**
     * Insert a new entry or old slug entry from gimli.
     * If old gimli entry, insert will happen with soft delete.
     * Once all slugs are migrated this method would not be used at all
     *
     * @param array $gimliResponse
     *
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function insertForHostedFlowWithGimliResponse(array $gimliResponse)
    {
        $urlAliases = array_get($gimliResponse, 'url_aliases', []);

        if (empty($urlAliases) == true)
        {
            return;
        }

        foreach ($urlAliases as $alias)
        {
            $metaData = array_get($alias, 'metadata', []);

            $aliasPaymentPageId = array_get($metaData, 'id');

            if (empty($metaData) === true || empty($aliasPaymentPageId) === true)
            {
                continue;
            }

            $paymentPage = $this->plRepo->findByPublicId($aliasPaymentPageId);

            if (empty($paymentPage) === true)
            {
                continue;
            }

            $this->viewType = $paymentPage->getViewType();

            [$url, $params, $fail] = (new PaymentLink\Core())->getShortenUrlRequestParams($paymentPage, $alias['hash']);

            $this->core->insertForHostedFlow([
                PaymentLink\NocodeCustomUrl\Entity::SLUG        => $alias['hash'],
                PaymentLink\NocodeCustomUrl\Entity::META_DATA   => $params['metadata'],
                PaymentLink\NocodeCustomUrl\Entity::DOMAIN      => Entity::determineDomainFromUrl($url),
            ], $paymentPage);
        }
    }

    /**
     * @param array                          $gimliResponse
     * @param \RZP\Models\PaymentLink\Entity $paymentPage
     *
     * @return void
     * @throws \Exception
     */
    private function upsertFromGimliResponse(array $gimliResponse, PaymentLink\Entity $paymentPage): void
    {
        $urlAliases = array_get($gimliResponse, 'url_aliases', []);

        if (empty($urlAliases) == true)
        {
            return;
        }

        $slug = $paymentPage->getSlugFromShortUrl();

        foreach ($urlAliases as $alias)
        {
            $metaData = array_get($alias, 'metadata', []);

            $aliasPaymentPageId = array_get($metaData, 'id');

            if (array_get($alias, 'hash') !== $slug
                || empty($metaData) === true
                || $aliasPaymentPageId !== $paymentPage->getPublicId())
            {
                continue;
            }

            $this->upsert($paymentPage, $alias);
        }
    }

    /**
     * @return string
     */
    private function getLastProcessedCreatedAtCacheKey(): string
    {
        return $this->config->get("app.nocode.prefix") . self::CACHE_TAG;
    }

    /**
     * @param int $createdAt
     *
     * @return void
     */
    private function cacheLastProcessedCreatedAt(int $createdAt): void
    {
        $this->cache->put($this->getLastProcessedCreatedAtCacheKey(), $createdAt, $this->cacheTTL);
    }

    /**
     * @return void
     */
    private function setLastProcessedCreatedAtFromCache(): void
    {
        $this->lastProcessedCreatedAt = $this->cache->get($this->getLastProcessedCreatedAtCacheKey());
    }
}
