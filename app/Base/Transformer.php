<?php

namespace RZP\Base;

use App;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;
use Selective\Transformer\ArrayTransformer;
use RZP\Models\Merchant\Transformers as Merchant;
use RZP\Models\ClarificationDetail\Transformers as ClarificationDetail;
use RZP\Models\Merchant\Detail\Transformers as MerchantDetail;
use RZP\Models\Merchant\Website\Transformers as MerchantWebsite;
use RZP\Models\Merchant\Document\Transformers as MerchantDocument;
use RZP\Models\Merchant\Stakeholder\Transformers as MerchantStakeholder;
use RZP\Models\Merchant\BvsValidation\Transformers as MerchantBvsValidation;
use RZP\Models\Merchant\BusinessDetail\Transformers as MerchantBusinessDetail;
use RZP\Models\Merchant\VerificationDetail\Transformers as MerchantVerificationDetail;

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
            $this->core->savePGOSDataToAPI($transformedData);
        }
    }

    public function getTransformers($tableName)
    {
        switch ($tableName)
        {
            case "merchants" :
                return [
                    new Merchant\MerchantsTransformer(),
                    new MerchantDetail\MerchantsTransformer(),
                    new MerchantStakeholder\MerchantsTransformer(),
                    new MerchantBusinessDetail\MerchantsTransformer()
                ];
                break;

            case "onboarding_details" :
                return [
                    new Merchant\OnboardingDetailsTransformer(),
                    new MerchantDetail\OnboardingDetailsTransformer(),
                ];
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
}
