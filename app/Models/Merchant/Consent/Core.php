<?php

namespace RZP\Models\Merchant\Consent;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Merchant\Consent\Details\Entity as DetailEntity;

class Core extends Base\Core
{

    /**
     * @param                $input
     *
     * @return mixed
     * @throws \Throwable
     */
    public function createMerchantConsents($input)
    {
        $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENTS,
                           [
                               Constants::INPUT => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($input) {

            $consent = new Entity();

            $consent->generateId();

            if (empty($input[DetailEntity::URL]) === false)
            {
                $details = $this->repo->merchant_consent_details->getByUrl($input[DetailEntity::URL]);

                if (empty($details) === true)
                {
                    $detailsInput = [DetailEntity::URL => $input[DetailEntity::URL]];

                    $details = (new Details\Core())->createConsentDetails($detailsInput);
                }

                $input[Entity::DETAILS_ID] = $details->getId();
            }

            unset($input[DetailEntity::URL]);

            $requestContext = $this->app[Constants::REQUEST_CTX];
            $request        = $this->app[Constants::REQUEST];

            if (isset($requestContext) === false or isset($request) === false)
            {
                return null;
            }

            $headers = $request->headers;

            if (isset($headers) === false)
            {
                return null;
            }

            $input += [
                Entity::STATUS      => 'pending',
                Entity::METADATA    => [
                    Constants::USER_AGENT => $headers->get(RequestHeader::X_USER_AGENT),
                    Constants::IP         => $headers->get(RequestHeader::X_DASHBOARD_IP)
                ],
                Entity::MERCHANT_ID => optional($this->app[Constants::BASIC_AUTH]->getMerchant())->getId() ?? '',
                Entity::USER_ID     => optional($this->app[Constants::BASIC_AUTH]->getUser())->getId() ?? ''
            ];

            $consent->build($input);

            $this->repo->merchant_consents->saveOrFail($consent);

            return $consent;
        });
    }
}
