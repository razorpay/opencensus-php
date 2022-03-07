<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Report;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Adjustment;
use RZP\Base\JitValidator;
use RZP\Constants\Entity as E;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class BasicEntityReport extends BaseReport
{
    use FileHandlerTrait;

    const MAX_FILE_LIMIT = 200000;

    protected $batchLimit = 20000;

    protected $entity;

    protected $report;

    protected $relationsToFetch;

    // Maps the entity to the relations that need to be fetched for it
    protected $entityToRelationFetchMap = [
        E::TRANSACTION  => [
            // Maps transaction source to entities that need to be fetched
            E::PAYMENT  => [E::ORDER, E::CARD],
            E::REFUND   => [
                E::PAYMENT,
                E::PAYMENT . '.' . E::CARD,
                E::PAYMENT . '.' . E::ORDER,
            ],
            E::ADJUSTMENT   => [
                Adjustment\Entity::ENTITY,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . E::CARD,
                Adjustment\Entity::ENTITY . '.' . E::PAYMENT . '.' . E::ORDER,
            ],
            E::SETTLEMENT,
        ],
        E::DISPUTE => [
            E::PAYMENT,
            E::PAYMENT . '.' . E::CARD,
            E::PAYMENT . '.' . E::ORDER,
        ],
        E::MERCHANT     => [],
        E::PAYMENT      => [E::CARD],
        E::REFUND       => [E::PAYMENT],
        E::ORDER        => [],
        E::SETTLEMENT   => [],
        E::TRANSFER     => [
            'recipientSettlement'
        ],
        E::REVERSAL     => [],
        E::INVOICE      => [E::ORDER],
    ];

    // Entities for which report-generation is allowed
    protected $allowed = [
        E::ORDER,
        E::REFUND,
        E::PAYMENT,
        E::SETTLEMENT,
        E::TRANSACTION,
        E::MERCHANT,
        E::TRANSFER,
        E::REVERSAL,
        E::INVOICE,
    ];

    public function __construct(string $entity)
    {
        parent::__construct();

        //
        // For linked account report, the entity is exposed as 'account' but is
        // the merchant entity.
        // Derived from BasicEntityReport
        // @todo: Change this when account onboarding goes live.
        //
        if ($entity === 'account')
        {
            $entity = 'merchant';
        }

        // @todo: Fix this!

        if ($entity === 'payment_link')
        {
            $entity = 'invoice';
        }

        $this->entity = $entity;

        $this->relationsToFetch = $this->entityToRelationFetchMap[$entity];
    }

    /**
     * Gets report data as array
     *
     * Not being used anywhere on dashboard
     * Keeping it to maintain backward compatibility
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @return $data array
     */
    public function getReport(array $input)
    {
        $this->validateInput($input);

        $this->setDefaults();

        list($from, $to, $count, $skip) = $this->getParamsForReport($input);

        // currently limiting the api response can break the merchant integration
        // so overwriting the limits for now
        list($count, $skip) = [200000, 0];

        $count = $input['count'] ?? $count;
        $skip  = $input['skip'] ?? $skip;

        list($data, $count) = $this->getReportData($from, $to, $count, $skip);

        return $data;
    }

    /**
     * Gets url for report data
     *
     * Not being used anywhere on dashboard
     * Keeping it to maintain backward compatibility
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @return array
     */
    public function getReportUrl(array $input)
    {
        $this->validateInput($input);

        $this->generateReport($input);

        $file = $this->report->file;

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        return ['url' => $signedUrl];
    }

    /**
     * 1. Pre report processing - increasing memory limit
     *
     * 2. Set report entity with params
     *
     * 3. Edits and saves report entity
     *
     * 4. Generate filename and fullpath
     *
     * 5. Saves file to AWS using UFH
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @return void
     */
    public function generateReport(array $input)
    {
        $this->setDefaults();

        $this->createReportEntity($input);

        $now = Carbon::now()->getTimestamp();

        $filename = $this->generateFilename($now);

        // We do not want all the aggregator merchant to download the complete report
        // so its behind aggregator_report feature
        if ($this->merchant->isFeatureEnabled(Feature\Constants::AGGREGATOR_REPORT) === true)
        {
            $fullpath = $this->writeDataToCsvForAggregator($input, $filename);
        }
        else
        {
            $fullpath = $this->writeDataToCsv($input, $filename);
        }

        $s3File = $this->createFileAndSave($fullpath, $filename);

        $this->editReportEntityAndSave($now, $s3File);

        $this->unlinkFile($fullpath);
    }

    /**
     * Generates report data and creates the csv file
     * We need to get the report for all the merchants
     * Currently, we are taking BATCH_LIMIT for each merchant's report
     * We will later modify the logic of how many enties of each merchant we want.
     *
     * @param  array  $input    [expected : 'day', 'month', 'year']
     * @param  string $filename
     *
     * @return string
     */
    protected function writeDataToCsvForAggregator(array $input, string $filename): string
    {
        list($from, $to, $originalCount, $originalSkip) = $this->getParamsForReport($input);

        $merchantIds = (new Merchant\Service)->getSubmerchants();

        $append = false;

        $totalEntries = 0;

        foreach ($merchantIds as $merchantId)
        {
            $count = $originalCount;
            $skip = $originalSkip;

            list($totalCount, $fullpath) = $this->writeDataToCsvForMerchant($from, $to, $count, $skip, $filename, $merchantId, $append);

            $totalEntries += $totalCount;

            $append = true;
        }

        if ($totalEntries > self::MAX_FILE_LIMIT)
        {
            $this->trace->critical(
                TraceCode::MERCHANT_REPORT_FILE_MAX_LIMIT_EXCEED,
                [
                    'merchant_id'   => $this->merchant->getId(),
                    'total_entries' => $totalEntries,
                ]);
        }

        return $fullpath;
    }

    /**
     * Generates report data and creates the csv file
     *
     * @param  array  $input    [expected : 'day', 'month', 'year']
     * @param  string $filename
     *
     * @return string
     */
    protected function writeDataToCsv(array $input, string $filename): string
    {
        list($from, $to, $count, $skip) = $this->getParamsForReport($input);

        $merchantId = $this->merchant->getId();

        list($totalCount, $fullpath) = $this->writeDataToCsvForMerchant($from, $to, $count, $skip, $filename, $merchantId);

        return $fullpath;
    }

    protected function writeDataToCsvForMerchant(int $from,
                                                 int $to,
                                                 int $count,
                                                 int $skip,
                                                 string $filename,
                                                 string $merchantId,
                                                 bool $append = false): array
    {
        $totalCount = 0;

        while ($count === $this->batchLimit)
        {
            list($data, $count) = $this->getReportDataForMerchant($from, $to, $this->batchLimit, $skip, $merchantId);

            $fullpath = $this->createCsvFile($data, $filename, null, 'files/report', $append);

            $skip += $count;

            $totalCount += $count;

            $append = true;
        }

        return [$totalCount, $fullpath];
    }

    /**
     * Gets params needed to generate report
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @return array
     */
    protected function getParamsForReport(array $input)
    {
        list($from, $to) = $this->getTimestamps($input);

        list($count, $skip) = $this->getFetchLimits($input);

        return [$from, $to, $count, $skip];
    }

    /**
     * Generates filename basis merchant_id, entity and timestamp
     *
     * @param  $timestamp
     * @return $filename string
     */
    protected function generateFilename($timestamp) : string
    {
        $merchantId = $this->merchant->getId();

        $filename = implode('_', [$merchantId, $this->entity, $timestamp]);

        return $filename;
    }

    /**
     * Gets limits in which the data is to be fetched from db
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @return array
     */
    protected function getFetchLimits($input): array
    {
        $count = $this->batchLimit;

        $skip = 0;

        if (isset($input['count']) === true)
        {
            $count = min($count, (int) $input['count']);
        }

        if (isset($input['skip']) === true)
        {
            $skip = (int) $input['skip'];
        }

        return [$count, $skip];
    }

    /**
     * unlinks the file from path after it is saved to AWS
     *
     * @param $fullpath string
     */
    protected function unlinkFile(string $fullpath)
    {
        if (file_exists($fullpath) === true)
        {
            unlink($fullpath); // nosemgrep : php.lang.security.unlink-use.unlink-use
        }
    }

    // ------ Data-fetching operations ------

    /**
     * Gets report data for concerned entity
     * 1. Fetches entities to be added in report
     * 2. Formats data to be shown in report
     *
     * @param  int    $from
     * @param  int    $to
     * @param  int    $count
     * @param  int    $skip
     * @return array
     */
    protected function getReportData(int $from, int $to, int $count, int $skip): array
    {
        $merchantId = $this->merchant->getId();

        return $this->getReportDataForMerchant($from, $to, $count, $skip, $merchantId);
    }

    /**
     * Gets report data for concerned entity
     * 1. Fetches entities to be added in report
     * 2. Formats data to be shown in report
     *
     * @param  int    $from
     * @param  int    $to
     * @param  int    $count
     * @param  int    $skip
     * @param  string $merchantId
     * @return array
     */
    protected function getReportDataForMerchant(int $from,
                                                int $to,
                                                int $count,
                                                int $skip,
                                                string $merchantId): array
    {
        $begin = time();

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'method'        => __METHOD__,
                'entity'        => $this->entity,
                'from'          => $from,
                'to'            => $to,
                'count'         => $count,
                'skip'          => $skip,
                'merchantId'    => $merchantId,
                'time_started'  => $begin
            ]);

        $entities = $this->fetchEntitiesForReport(
                                $merchantId, $from, $to, $count, $skip);

        $timeTaken = time() - $begin;

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'method'        => __METHOD__,
                'entity'        => $this->entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId,
                'time_taken'    => $timeTaken
            ]);

        $formattedData = $this->fetchFormattedDataForReport($entities);

        $timeTaken = time() - $begin;

        $this->trace->debug(
            TraceCode::MERCHANT_REPORT_GENERATION,
            [
                'method'        => __METHOD__,
                'entity'        => $this->entity,
                'from'          => $from,
                'to'            => $to,
                'merchantId'    => $merchantId,
                'time_taken'    => $timeTaken
            ]);

        $fetchCount = $entities->count();

        return [$formattedData, $fetchCount];
    }

    /**
     * Returns formatted data to be shown in report
     *
     * @param $entities array
     * @return array
     */
    protected function fetchFormattedDataForReport($entities): array
    {
        return $entities->toArrayReport();
    }

    /**
     * Returns all the data to be shown in report
     * This function is overridden in concerned repo
     * If not, it is executed from base repo
     *
     * @param $merchantId
     * @param $from
     * @param $to
     * @param $count
     * @param $skip
     *
     * @return \RZP\Base\PublicCollection
     */
    protected function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip)
    {
        $entity = $this->entity;

        $repo = $this->repo->$entity;

        return $repo->fetchEntitiesForReport(
                        $merchantId,
                        $from,
                        $to,
                        $count,
                        $skip,
                        $this->relationsToFetch);
    }

    // ------ Write operations ------

    /**
     * Sets report entity by building it from params
     * The resultant report entity is not yet saved to DB
     *
     * @param $input array
     *               expected : 'day', 'month', 'year'
     */
    protected function createReportEntity(array $input)
    {
        list($from, $to) = $this->getTimestamps($input);

        $params = [
            'from'      => $from,
            'to'        => $to,
            'entity'    => $this->entity,
        ];

        $params = array_merge($input, $params);

        $report = (new Report\Core)->buildEntity($params, $this->merchant);

        $this->report = $report;
    }

    /**
     * Creates uploaded file &
     * Uses UFH to save file to s3
     *
     * @param  $filePath string
     * @param  $fileName string
     *
     * @return FileStore\Entity $s3File
     *
     * @throws Exception\LogicException
     */
    protected function createFileAndSave($filePath, $fileName)
    {
        $creator = new FileStore\Creator;

        $s3File = $creator->localFilePath($filePath)
                          ->extension(FileStore\Format::CSV)
                          ->mime('application/octet-stream')
                          ->name('reports/' . $fileName)
                          ->store(FileStore\Store::S3)
                          ->type(FileStore\Type::REPORT)
                          ->merchant($this->merchant)
                          ->save()
                          ->getFileInstance();

        return $s3File;
    }

    /**
     * Set generated_at & file_id for report entity,
     * Save the report entity.
     *
     * generated_at is set as `$now` because it represents the time
     * right before `getReportData` is envoked.
     *
     * Not storing file as foreign key because we only need the id
     * to get the signed url from FileStore Service
     *
     * @param  $generatedAt integer
     * @param  $file        FileStore\Entity
     */
    protected function editReportEntityAndSave($generatedAt, FileStore\Entity $file)
    {
        $report = $this->report;

        // set generatedAt value for report
        $report->setGeneratedAt($generatedAt);

        // associate file with report
        $report->file()->associate($file);

        $this->repo->saveOrFail($report);
    }

    // ------ Processes before starting report-generation ------

    /**
     * 1. Validates Input
     * 2. Checks if entity is allowed for report
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     */
    protected function validateInput(array $input)
    {
        (new JitValidator)->rules(self::$rules)->input($input)->validate();

        $this->checkAllowedEntity();
    }

    /**
     * Checks if the entity is allowed to be made a report of
     * Thows exception
     */
    protected function checkAllowedEntity()
    {
        if (in_array($this->entity, $this->allowed, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot get report for the given entity');
        }

        if (($this->entity === E::MERCHANT) and
            ($this->merchant->isMarketplace() === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Exporting this data is not allowed for the merchant');
        }
    }
}
