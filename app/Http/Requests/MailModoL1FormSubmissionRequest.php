<?php

namespace RZP\Http\Requests;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\AMPEmail\Service;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Merchant\Detail\BusinessSubcategory;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\AMPEmail\Constants;
use RZP\Exception\BadRequestException;
use RZP\Models\AMPEmail\MailModoService;
use Illuminate\Foundation\Http\FormRequest;
use RZP\Models\Merchant\BusinessDetail\Constants as BusinessConstant;

class MailModoL1FormSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $app = App::getFacadeRoot();

        $app['trace']->info(TraceCode::MAILMODO_SUBMISSION_REQUEST, [
            'request' => $this->getContent()
        ]);

        $service = new MailModoService();

        $service->validateCors();

        $app['trace']->info(TraceCode::MAILMODO_SUBMISSION_REQUEST, [
            'cors' => 'validated'
        ]);

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            Constants::TOKEN => 'required|string',
        ];
    }


    public function getToken()
    {
        $request = $this->all();

        if (array_key_exists(Constants::TOKEN, $request)===true)
        {
            return $request[Constants::TOKEN];
        }
        else
        {
            return null;
        }
    }

    public function getInputFields()
    {

        $input = $this->all();

        $finalInput = [];

        foreach ($input as $key => $value)
        {
            if (array_search($key, Constants::L1_FORM_FIELDS) && empty($value) === false)
            {
                $finalValue = urldecode($value);

                switch ($key)
                {
                    case DetailEntity::BUSINESS_REGISTERED_ADDRESS:

                        $finalInput[DetailEntity::BUSINESS_OPERATION_ADDRESS] = $finalValue;

                        break;

                    case DetailEntity::BUSINESS_REGISTERED_CITY:

                        $finalInput[DetailEntity::BUSINESS_OPERATION_CITY] = $finalValue;

                        break;

                    case DetailEntity::BUSINESS_REGISTERED_STATE:

                        $finalInput[DetailEntity::BUSINESS_OPERATION_STATE] = $finalValue;

                        break;

                    case DetailEntity::BUSINESS_REGISTERED_PIN:

                        $finalInput[DetailEntity::BUSINESS_OPERATION_PIN] = $finalValue;

                        break;

                    case "payments_mode":

                        $finalValue = urldecode($finalValue);

                        if ($finalValue == "On my website/app")
                        {
                            $finalInput["business_website"] = urldecode($input["business_website"]);
                            $playstore_url                  = urldecode($input["playstore_url"]);

                            if (empty($playstore_url) === false)
                            {
                                $finalInput["playstore_url"] = urldecode($input["playstore_url"]);
                            }
                        }
                        else
                        {
                            $finalInput["business_website"] = "";
                            $finalInput["playstore_url"]    = "";
                        }

                        break;
                }

                if ($key != 'payments_mode')
                {
                    $finalInput[$key] = $finalValue;
                }
            }
        }

        return $finalInput;
    }

    public function isL1FormSubmission()
    {
        $request = $this->all();

        return array_key_exists("final", $request)===true && $request["final"] === true;
    }
}
