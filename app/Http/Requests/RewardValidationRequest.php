<?php

namespace RZP\Http\Requests;

use App;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use Illuminate\Foundation\Http\FormRequest;
use RZP\Models\Merchant\Detail\Metric as DetailMetric;
use RZP\Models\Merchant\M2MReferral\FriendBuy\Constants;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;
use RZP\Models\Merchant\M2MReferral\FriendBuy\FriendBuyClient;
use RZP\Models\Merchant\M2MReferral\FriendBuy\FriendBuyService;

class RewardValidationRequest extends FormRequest
{
     /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $app = App::getFacadeRoot();

        $app['trace']->info(TraceCode::FRIEND_BUY_REWARD_VALIDATION_REQUEST, [
            'request' => $this->getContent()
        ]);

        return (new FriendBuyService())->validateSignature($this);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            Constants::EVENT_TYPE => 'required|string',

            Constants::RECIPIENT_TYPE => 'required|string|in:advocate,friend',

            Constants::ADVOCATE => 'required|array',

            Constants::ADVOCATE . '.' . Constants::CUSTOMER_ID => 'required|string|size:14',

            Constants::ACTOR => 'required|array',

            Constants::ACTOR . '.' . Constants::CUSTOMER_ID => 'required|string|size:14',

        ];
    }

    public function getMerchantId()
    {
        $request = $this->all();

        return $request[Constants::ACTOR][Constants::CUSTOMER_ID];
    }

    public function getReferrerId()
    {
        $request = $this->all();

        return $request[Constants::ADVOCATE][Constants::CUSTOMER_ID];
    }
}
