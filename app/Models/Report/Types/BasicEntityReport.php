<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Exception;
use RZP\Models\Report;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Base\JitValidator;
use RZP\Base\RuntimeManager;
use RZP\Constants\Entity as E;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class BasicEntityReport extends BaseReport
{
    use FileHandlerTrait;

    const BATCH_LIMIT = 20000;

    // Maps the entity to the relations that need to be fetched for it
    protected $entityToRelationFetchMap = [
        E::TRANSACTION  => [
            // Maps transaction source to entities that need to be fetched
            E::PAYMENT  => [E::ORDER],
            E::REFUND   => [
                E::PAYMENT,
                E::PAYMENT . '.' . E::ORDER
            ],
        ],
        E::MERCHANT     => [],
        E::PAYMENT      => [E::CARD],
        E::REFUND       => [E::PAYMENT],
        E::ORDER        => [],
        E::SETTLEMENT   => [],
        E::TRANSFER     => [],
        E::REVERSAL     => [],
    ];

    protected $entity;

    protected $relationsToFetch;

    protected $allowed = array(
        E::ORDER,
        E::REFUND,
        E::PAYMENT,
        E::SETTLEMENT,
        E::TRANSACTION,
        E::MERCHANT,
        E::TRANSFER,
        E::REVERSAL,
    );

    public function __construct(string $entity)
    {
        parent::__construct();

        //
        // For linked account report, the entity is exposed as 'account' but is
        // the merchant entity.
        // @todo: Change this when account onboarding goes live.
        //
        if ($entity === 'account')
        {
            $entity = 'merchant';
        }

        $this->entity = $entity;

        $this->relationsToFetch = $this->entityToRelationFetchMap[$this->entity];
    }

    public function getReport(array $input)
    {
        $this->preReportProcessing($input);

        list($from, $to) = $this->getTimestamps($input);

        //list($count, $skip) = $this->getFetchLimits($input);

        // currently limiting the api response can break the merchant integration
        // so overwriting the limits for now
        list($count, $skip) = [200000, 0];

        list($data, $count) = $this->getReportData($from, $to, $count, $skip);

        return $data;
    }

    public function getReportUrl(array $input): array
    {
        $this->preReportProcessing($input);

        list($from, $to) = $this->getTimestamps($input);

        list($count, $skip) = $this->getFetchLimits($input);

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $merchantId = $this->merchant->getId();

        $fileName = $merchantId . '_' . $this->entity . '_' . $now;

        $append = false;

        // get or create report entity
        $entityParams = [
            'from'   => $from,
            'to'     => $to,
            'entity' => $this->entity
        ];

        $report = (new Report\Core)->buildEntity($entityParams + $input, $this->merchant);

        // set generatedAt value for report
        // this is set as `now` because
        // generated_at represents the time
        // right before `getReportData` is envoked
        $report->setGeneratedAt($now);

        while ($count === self::BATCH_LIMIT)
        {
            list($data, $count) = $this->getReportData($from, $to, self::BATCH_LIMIT, $skip);

            $fullpath = $this->createCsvFile($data, $fileName, null, 'files/report', $append);

            $skip += $count;

            $append = true;
        }

        $s3File = $this->createFileAndSave($fullpath, $fileName);

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($s3File);

        // set file/UFH
        $report->file()->associate($s3File);

        // save changes to report
        $this->repo->saveOrFail($report);

        if (file_exists($fullpath) === true)
        {
            unlink($fullpath);
        }

        return ['url' => $signedUrl];
    }

    protected function getReportData($from, $to, $count, $skip): array
    {
        $merchantId = $this->merchant->getId();

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
     * 1. Checks if entity is allowed for report
     * 2. Increases system limits
     * 3. Validates input
     * 4. Sets timezone
     *
     * @param $input array
     */
    protected function preReportProcessing(array $input)
    {
        $this->checkAllowedEntity();

        $this->increaseAllowedSystemLimits();

        (new JitValidator)->rules(self::$rules)->input($input)->validate();

        date_default_timezone_set('Asia/Kolkata');
    }

    /**
     * Checks if the entity is allowed
     * to be made a report of
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

    protected function fetchFormattedDataForReport($entities): array
    {
        return $entities->toArrayReport();
    }

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

    protected function getFetchLimits($input): array
    {
        $count = self::BATCH_LIMIT;
        $skip = 0;

        if (isset($input['count']))
        {
            $count = min($count, (int) $input['count']);
        }

        if (isset($input['skip']))
        {
            $skip = (int) $input['skip'];
        }

        return [$count, $skip];
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(501);
    }

    /**
     * Creates uploded file &
     * Uses UFH to save file to s3
     *
     * @param  $filePath string
     * @param  $fileName string
     * @return $s3File   array containing fileId and url
     */
    protected function createFileAndSave($filePath, $fileName)
    {
        $creator = new FileStore\Creator;

        $s3File = $creator->localFilePath($filePath)
                          ->extension(FileStore\Format::CSV)
                          ->mime('text/csv')
                          ->name('reports/' . $fileName)
                          ->store(FileStore\Store::S3)
                          ->type(FileStore\Type::REPORT)
                          ->merchant($this->merchant)
                          ->save()
                          ->getFileInstance();

        return $s3File;
    }
}
