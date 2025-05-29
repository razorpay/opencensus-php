<?php

namespace RZP\Base;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;
use RZP\Models\User\Transformers as User;
use Selective\Transformer\ArrayTransformer;
use RZP\Models\Merchant\Transformers as Merchant;
use RZP\Models\Merchant\Detail\Transformers as MerchantDetail;
use RZP\Models\Merchant\Website\Transformers as MerchantWebsite;
use RZP\Models\Merchant\Document\Transformers as MerchantDocument;
use RZP\Models\ClarificationDetail\Transformers as ClarificationDetail;
use RZP\Models\Merchant\Stakeholder\Transformers as MerchantStakeholder;
use RZP\Models\Merchant\BvsValidation\Transformers as MerchantBvsValidation;
use RZP\Models\Merchant\BusinessDetail\Transformers as MerchantBusinessDetail;
use RZP\Models\Merchant\VerificationDetail\Transformers as MerchantVerificationDetail;

const APPLY_MUTEX_ON_PGOS_DUAL_WRITE_EXPERIMENT_ID = 'app.apply_mutex_on_pgos_dual_write_experiment_id';
const REMOVE_VERIFICATION_DETAILS_DUAL_WRITE_ON_MERCHANT_DETAILS_AND_STAKEHOLDER_EXPERIMENT_ID = 'app.remove_verification_details_dual_write_on_merchant_details_and_stakeholder_experiment_id';

class Transformer
{

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Repository manager instance
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     *
     * @var Trace
     */
    protected $trace;


    protected $core;


    protected $rules = [];


    protected $mutex;


    protected $splitz;


    const MUTEX_LOCK_TTL = 60; // in seconds
    const MUTEX_RETRY_COUNT = 10;
    const MUTEX_MIN_RETRY_DELAY = 1000; // in miliseconds
    const MUTEX_MAX_RETRY_DELAY = 2000; // in miliseconds

    const AUDIT_ID = 'audit_id';

    /* $rules //used to do structure conversion
    format = [
        input column name  1   => [
            [
                "column"    => output column name 1,
                'condition' => [ //used for row to column mapping - all are and conditions if met we will choose output column name 1 for input column name  1
                    input column name 1   => value 1,
                    input column name 2   => value 2,
             ],
                'function' => function name 1 // this is used for data conversion
            ],
            [
                "column"    => output column name 2,
                'condition' => [ //used for row to column mapping - all are and conditions if met we will choose output column name 2 for input column name  1
                    input column name 1   => value 3,
                    input column name 2   => value 4,
             ]
            ],
        ]
    ];
    */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->mutex = $this->app['api.mutex'];

        $this->splitz = $this->app['splitzService'];
    }

    protected function registerFilters(ArrayTransformer $transformer)
    {

    }

    public function transform($inputData)
    {
        $transformer = new ArrayTransformer();

        $this->registerFilters($transformer);

        foreach ($this->rules as $key => $columnsData)
        {
            foreach ($columnsData as $columnData)
            {
                $functionName = $columnData['function'] ?? null;

                if (isset($columnData['condition']) === false)
                {
                    if (empty($functionName) === true)
                    {
                        $transformer->map($columnData["column"], $key);
                    }
                    else
                    {
                        $transformer->map($columnData["column"], $key, $functionName);
                    }
                }
                else
                {
                    $conditionsSatisfied = true;

                    foreach ($columnData['condition'] as $columnName => $value)
                    {
                        if ($inputData[$columnName] !== $value)
                        {
                            $conditionsSatisfied = false;
                        }
                    }
                    if ($conditionsSatisfied === true)
                    {
                        if (empty($functionName) === true)
                        {
                            $transformer->map($columnData["column"], $key);
                        }
                        else
                        {
                            $transformer->map($columnData["column"], $key, $functionName);
                        }
                    }
                }
            }
        }

        $transformedData = $transformer->toArray($inputData);

        if (count($transformedData) > 0)
        {
            $merchantId = $inputData['merchant_id'] ?? $inputData['id'];

            if ($merchantId != null and $this->shouldApplyMutexOnPGOSDualWrite($merchantId) == true) {
                $this->mutex->acquireAndRelease(
                    $merchantId,
                    function() use ($transformedData)
                    {
                        $this->trace->info(TraceCode::MUTEX_ON_PGOS_DUAL_WRITE, [
                            'acquired' => true,
                        ]);

                        unset($transformedData[self::AUDIT_ID]);

                        $this->core->savePGOSDataToAPI($transformedData);
                    },
                    self::MUTEX_LOCK_TTL,
                    ErrorCode::BAD_REQUEST_MERCHANT_EDIT_OPERATION_IN_PROGRESS,
                    self::MUTEX_RETRY_COUNT,
                    self::MUTEX_MIN_RETRY_DELAY,
                    self::MUTEX_MAX_RETRY_DELAY
                );
            }
            else {

                $this->trace->info(TraceCode::MUTEX_ON_PGOS_DUAL_WRITE, [
                    'acquired' => false,
                ]);

                unset($transformedData[self::AUDIT_ID]);

                $this->core->savePGOSDataToAPI($transformedData);
            }

        }
    }

    public function getTransformers($tableName, $merchantId)
    {
        switch ($tableName)
        {
            case "merchants" :
                return [
                    new Merchant\MerchantsTransformer(),
                    new MerchantDetail\MerchantsTransformer(),
                    new MerchantStakeholder\MerchantsTransformer(),
                    new MerchantBusinessDetail\MerchantsTransformer(),
                    new User\MerchantsTransformer()
                ];
                break;

            case "onboarding_details" :
                return [];
                break;
            case "website_details"  :
                return [
                    new MerchantWebsite\WebsiteDetailTransformer(),
                    new Merchant\WebsiteDetailTransformer(),
                    new MerchantDetail\WebsiteDetailsTransformer(),
                    new MerchantBusinessDetail\WebsiteDetailTransformer(),
                ];
                break;
            case "verification_details"  :
                // if the experiment is on, instead of dual-write, PGOS will call ASV for merchant_details and stakeholder table
                if ($this->shouldRemoveDualWriteOnMerchantDetailsAndStakeholder($merchantId) === true)
                {
                    return [
                        new MerchantVerificationDetail\VerificationDetailTransformer(),
                        new MerchantBvsValidation\VerificationDetailsTransformer(),
                    ];
                }
                return [
                    new MerchantVerificationDetail\VerificationDetailTransformer(),
                    new MerchantBvsValidation\VerificationDetailsTransformer(),
                    new MerchantStakeholder\VerificationDetailsTransformer(),
                    new MerchantDetail\VerificationDetailsTransformer(),
                ];
                break;
            case "documents"  :
                return [
                    new MerchantDocument\DocumentTransformer(),
                ];
                break;
            case "clarification_details"  :
                return [
                    new ClarificationDetail\ClarificationDetailsTransformer(),
                ];
                break;
        }
    }

    private function shouldRemoveDualWriteOnMerchantDetailsAndStakeholder($merchantId)
    {
        $mode = 'enable';

        $experimentId = $this->app['config']->get(REMOVE_VERIFICATION_DETAILS_DUAL_WRITE_ON_MERCHANT_DETAILS_AND_STAKEHOLDER_EXPERIMENT_ID);

        $splitzId = $merchantId;

        if ($splitzId === null)
        {
            $splitzId = UniqueIdEntity::generateUniqueId();

            $this->trace->info(TraceCode::MERCHANT_ID_NOT_FOUND_IN_PGOS_DUAL_WRITE, [
                'splitz_id' => $splitzId,
            ]);
        }

        try
        {
            $properties = [
                'id'            => $splitzId,
                'experiment_id' => $experimentId,
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::REMOVE_VERIFICATION_DETAILS_DUAL_WRITE_ON_MERCHANT_DETAILS_AND_STAKEHOLDER_SPLITZ_CALL, [
                'splitz_output' => $variant,
            ]);

            return $variant === $mode;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'id'   => $splitzId,
                'experiment_id' => $experimentId ?? null,
            ]);

            return false;
        }
    }

    public function shouldApplyMutexOnPGOSDualWrite(string $merchantId)
    {
        $mode = 'enable';

        try
        {
            $properties = [
                'id'            => $merchantId,
                'experiment_id' => $this->app['config']->get(APPLY_MUTEX_ON_PGOS_DUAL_WRITE_EXPERIMENT_ID),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::APPLY_MUTEX_ON_PGOS_DUAL_WRITE_SPLITZ_CALL, [
                'splitz_output' => $variant,
            ]);

            return $variant === $mode;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id'   => $merchantId,
                'experiment_id' => $this->app['config']->get(APPLY_MUTEX_ON_PGOS_DUAL_WRITE_EXPERIMENT_ID) ?? null
            ]);

            return false;
        }
    }
}
