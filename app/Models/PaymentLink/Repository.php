<?php

namespace RZP\Models\PaymentLink;

use Carbon\Carbon;

use App;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    protected $entity = 'payment_link';

    protected $expands = [
        Entity::PAYMENT_PAGE_ITEMS,
        Entity::PAYMENT_PAGE_ITEMS . '.' . PaymentPageItem\Entity::ITEM,
    ];

    /**
     * Gets all ACTIVE status payment links which are past EXPIRE_BY.
     * Payment links which are in INACTIVE status are not affected.
     *
     * @return Base\PublicCollection
     */
    public function getActiveAndPastExpireByPaymentLinks(): Base\PublicCollection
    {
        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::ACTIVE)
                    ->where(Entity::EXPIRE_BY, '<', $currentTime)
                    ->get();
    }

    /**
     * Finds payment link entity by public id constrained to not being marked
     * inactive with reason deactivated(manually).
     *
     * @param  string $id
     * @return Entity
     */
    public function findActiveByPublicId(string $id): Entity
    {
        $app = \App::getFacadeRoot();

        $entity = $this->findByPublicId($id);

        // No direct query with filter because index is as (status, status_reason).
        if ($entity->isDeactivated() === true)
        {
            $merchantDetails = $entity->getMerchantSupportDetails();

            $orgBrandingDetails = $entity->getMerchantOrgBrandingDetails();

            $isCurlec = $orgBrandingDetails['is_curlec_org'];

            $reportLinkUrl = '';

            if($isCurlec === true)
            {
                $reportLinkUrl = $app['config']->get('app.curlec_customer_flagging_report_url');
            }
            else
            {
                $reportBaseUrl = $app['config']->get('app.customer_flagging_report_url');

                $params = http_build_query([
                    'e'  => base64_encode($entity->getPublicId()),
                    's'  => base64_encode('hosted'),
                ]);

                $reportLinkUrl = $reportBaseUrl . $params;
            }

            $data['merchant'] = $merchantDetails;

            $data['org'] = $orgBrandingDetails;

            $data['entity'] = [Entity::ID => $entity->getPublicId()];

            $data['mode'] = $this->app['rzp.mode'];

            $data['view_type'] = $entity->getViewType();

            $data['report_link_url'] = $reportLinkUrl;

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID, null, $data, 'This page has been deactivated');
        }

        return $entity;
    }
}
