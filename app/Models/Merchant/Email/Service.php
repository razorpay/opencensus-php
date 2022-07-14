<?php

namespace RZP\Models\Merchant\Email;

use DB;
use Mail;
use Cache;
use Config;
use Request;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Services\Segment\Constants as SegmentConstants;

class Service extends Base\Service
{
    /**
     * Creates or edits a merchant's  different type of emails and saves in databases array
     *
     * @param string $merchantId
     * @param        $input
     *
     * @return array
     */
    public function createEmails(string $merchantId, $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $emails = $this->core()->upsert($merchant, $input);

        return $emails->toArrayPublic();
    }

    /**
     * Fetch a merchant's  different type of emails from databases as an array
     *
     * @param string $merchantId
     *
     * @return array
     */
    public function fetchEmails(string $merchantId): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $emails = $this->core()->fetchAllEmails($merchant);

        return $emails->toArrayPublic();
    }

    /**
     * Fetch a merchant's  single type of emails from databases as an array
     *
     * @param string $merchantId
     * @param string $type
     *
     * @return array
     */
    public function fetchEmailByType(string $merchantId, string $type): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $emails = $this->core()->fetchEmailsByType($merchant, $type);

        return $emails->toArrayPublic();
    }

    /**
     * Delete a merchant's  different type of emails from databases as an array
     *
     * @param $merchantId
     * @param $type
     *
     * @return array
     */
    public function deleteEmailByType($merchantId, $type): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $deleteOperation = $this->core()->deleteEmails($merchant, $type);

        return (array) $deleteOperation;
    }

    /**
     * Fetch emails aggregated on merchant level by types
     *
     * @param array $merchantIds
     * @param array $types
     * @return array
     */
    public function fetchEmailByMerchantIdsAndTypes(array $merchantIds, array $types): array
    {
        $emailsArray = $this->core()->fetchEmailByMerchantIdsAndTypes($merchantIds, $types);

        // For aggregating mails on merchant level by type and exploding emails
        $merchantEmailMap = [];

        foreach ($emailsArray as $emailArray)
        {
            $merchantEmailMap[$emailArray[Entity::MERCHANT_ID]][$emailArray[Entity::TYPE]] = array_map(
                'trim',
                explode(',', $emailArray['email'])
            );
        }

        return $merchantEmailMap;
    }

    public function getSupportDetails(Merchant\Entity $merchant)
    {
        try
        {
            $supportDetails = $this->core()->fetchEmailsByType($merchant, Type::SUPPORT);

            return $supportDetails->toArrayPublic();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return null;
        }
    }

    public function proxyGetSupportDetails(Merchant\Entity $merchant): array
    {
        $supportDetails = $this->core()->fetchEmailsByType($merchant, Type::SUPPORT);

        return $supportDetails->toArrayPublic();
    }

    public function proxyCreateSupportDetails(Merchant\Entity $merchant, array $input): array
    {
        $input[Entity::TYPE] = Type::SUPPORT;

        $supportDetails = $this->core()->upsert($merchant, $input);

        $this->pushSelfServeSuccessEventsToSegmentForMerchantSupportDetailsUpdate($merchant);

        return $supportDetails->toArrayPublic();
    }

    public function proxyEditSupportDetails(Merchant\Entity $merchant, array $input): array
    {
        $input[Entity::TYPE] = Type::SUPPORT;

        $supportDetails = $this->core()->upsert($merchant, $input);

        $this->pushSelfServeSuccessEventsToSegmentForMerchantSupportDetailsUpdate($merchant);

        return $supportDetails->toArrayPublic();
    }

    public function updateChargebackPOC($input): array
    {
        $status = 'success';

        $errorMsg = '';

        try
        {
            switch (strtolower($input['action']))
            {
                case 'insert':
                    $this->core()->addEmail($input['merchant_id'], Merchant\Email\Type::CHARGEBACK, $input['email']);
                    break;
                case 'delete':
                    $this->core()->removeEmail($input['merchant_id'], Merchant\Email\Type::CHARGEBACK, $input['email']);
                    break;
                default:
                    throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ACTION);
            }
        }
        catch (\Throwable $e)
        {
            $status = 'failure';

            $errorMsg = sprintf('ERROR: %s', $e->getMessage());
        }

        $input['status'] = $status;

        $input['error_message'] = $errorMsg;

        return $input;
    }

    private function pushSelfServeSuccessEventsToSegmentForMerchantSupportDetailsUpdate(Merchant\Entity $merchant)
    {
        $segmentProperties = [];

        $segmentEventName = SegmentEvent::SELF_SERVE_SUCCESS;

        $segmentProperties[SegmentConstants::OBJECT] = SegmentConstants::SELF_SERVE;

        $segmentProperties[SegmentConstants::ACTION] = SegmentConstants::SUCCESS;

        $segmentProperties[SegmentConstants::SOURCE] = SegmentConstants::BE;

        $segmentProperties[SegmentConstants::SELF_SERVE_ACTION] = 'Merchant Support Details Updated';

        $this->app['segment-analytics']->pushIdentifyAndTrackEvent(
            $merchant, $segmentProperties, $segmentEventName
        );
    }
}
