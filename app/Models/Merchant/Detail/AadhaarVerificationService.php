<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Merchant\AutoKyc\Bvs\Factory;
use RZP\Models\Merchant\Detail\Core as DetailCore;

class AadhaarVerificationService extends Base\Core
{

    /**
     * AadhaarVerificationService constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param                 $merchant
     * @param                 $merchantDetails
     * @param                 $input
     *
     *
     * @return array|null
     * @throws \RZP\Exception\LogicException
     */
    public function merchantIdentityVerification($merchant, $merchantDetails, $input)
    {
        $merchantDetails->getValidator()->validateInput('digilockerUrl', $input);

        $response = null;

        $createdAt = Carbon::now()->getTimestamp();

        $referenceId = (new Entity)->generateUniqueIdFromTimestamp($createdAt);

        $param = [
            'reference_id' => $referenceId,
            'redirect_url' => $input['redirect_url']
        ];

        $processor = (new Factory())->getProcessor($param, $merchant);

        $response = $processor->getVerificationUrl($param);

        $stakeholderInput[Stakeholder\Entity::VERIFICATION_METADATA] = [
            'reference_id' => $referenceId,
        ];

        (new Stakeholder\Core)->createOrEditStakeholder($merchantDetails, $stakeholderInput);

        return $response;
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @param                 $merchantDetails
     *
     * @return array|null
     * @throws \RZP\Exception\LogicException
     */
    public function processIdentityVerificationDetails(Merchant\Entity $merchant, $merchantDetails): ?array
    {
        $stakeholder = (new Stakeholder\Core)->createOrFetchStakeholder($merchantDetails);

        $referenceId = $stakeholder->getValueFromVerificationMetaData('reference_id');

        if ($referenceId === null)
        {
            $this->trace->info(
                TraceCode::REFERENCE_ID_DOES_NOT_EXIST,
                [
                    'merchant_id' => $merchantDetails->getMerchantId(),
                ]
            );

            return [];
        }

        $param = [
            'reference_id'         => $referenceId,
            'require_aadhaar_file' => true
        ];

        $response = null;

        $processor = (new Factory())->getProcessor($param, $merchant);

        $response = $processor->fetchVerificationDetails($param);

        if (isset($response['is_valid']) and $response['is_valid'] === true)
        {
            (new DetailCore)->processDigilockerAadhaarVerification(
                $merchant->getId(), $response['file_url'], $response['probe_id']);

            unset($response['probe_id']);

            unset($response['file_url']);
        }

        return $response;
    }
}
