<?php

namespace RZP\Mail\BankingAccount;

use Carbon\Carbon;

use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Activation\Detail;
use RZP\Models\BankingAccount\Constants as BankingAccountConstants;

class XProActivation extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.x_pro_activation';

    const MAIL_TAG      = MailTags::BANKING_ACCOUNT_X_PRO_ACTIVATION;

    const SUBJECT       = 'RazorpayX | Current Account [%s | %s]';

    // Constants used within Mail
    const SKIP_DWT_STATUS                                  = 'skip_dwt_status';

    const DOCKET_ADDRESS_DIFFERENT_FROM_REGISTERED_ADDRESS = 'docket_address_different_from_registered_address';

    const ELIGIBLE_FOR_SKIP_DWT                            = 'ELIGIBLE_FOR_SKIP_DWT';

    const PROCEED_WITH_DWT                                 = 'PROCEED_WITH_DWT';

    /**
     * This email is sent to ops to notify them about the interest merchant has shown in
     * X Pro plan, currently that is RBL current account
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->toEmail      = Constants::X_SUPPORT;
        $this->fromEmail    = Constants::NOREPLY;
        $this->replyToEmail = Constants::NOREPLY;

        parent::__construct($data);
    }

    protected function getSubject()
    {
        $merchantId = $this->data[Entity::MERCHANT][Entity::ID];

        $merchantName = $this->data[Entity::MERCHANT]['name'];

        return sprintf(self::SUBJECT, $merchantId, $merchantName);
    }

    protected function getMailData()
    {
        $data = $this->data;

        $merchant = $data[Entity::MERCHANT];

        $createdAt = $data[Entity::CREATED_AT];

        $dateTime = Carbon::createFromTimestamp($createdAt, Timezone::IST)->format('d-M-y H:i');

        $slotBookingDateTime = "";

        $spocEmail = "";

        if ($data[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][Detail\Entity::BOOKING_DATE_AND_TIME] != null)
        {
            $slotBookingDateTime = Carbon::createFromTimestamp($data[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][Detail\Entity::BOOKING_DATE_AND_TIME])->format('d-M-y H:i');
        }

        $spocDetail = array_key_exists(Entity::BANKING_ACCOUNT_CA_SPOC_DETAILS, $data);

        if ($spocDetail === true)
        {
            $spocEmail = $data[Entity::BANKING_ACCOUNT_CA_SPOC_DETAILS][Detail\Entity::SALES_POC_EMAIL];
        }

        $green_channel_value = "No";

        $skipDwtExpFields = [];

        if ($data[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][Detail\Entity::ADDITIONAL_DETAILS] != null)
        {

            $additional_details = json_decode($data[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][Detail\Entity::ADDITIONAL_DETAILS], true);

            if (array_key_exists("green_channel", $additional_details))
            {
                $green_channel_value = ($additional_details["green_channel"]) ? "Yes" : "No";
            }

            if (isset($additional_details[Detail\Entity::SKIP_DWT]))
            {
                $skipDwtStatus = $additional_details[Detail\Entity::SKIP_DWT] === 1 ?
                    self::ELIGIBLE_FOR_SKIP_DWT : self::PROCEED_WITH_DWT;

                $skipDwtExpFields = array_merge($skipDwtExpFields,
                    [self::SKIP_DWT_STATUS => $skipDwtStatus]);
            }

            if (isset($additional_details[Detail\Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS]
                [Detail\Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS]))
            {
                $availableAtPreferredAddressToCollectDocs = $additional_details[Detail\Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS]
                    [Detail\Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS] === 1 ? 'YES' : 'NO';

                $skipDwtExpFields = array_merge($skipDwtExpFields,
                    [self::DOCKET_ADDRESS_DIFFERENT_FROM_REGISTERED_ADDRESS => $availableAtPreferredAddressToCollectDocs]);
            }

        }

        $data = [
            'internal_reference_number' => $data[Entity::BANK_REFERENCE_NUMBER],
            'merchant_name'             => $merchant[Merchant\Entity::NAME],
            'merchant_id'               => $merchant[Merchant\Entity::ID],
            'merchant_email'            => $merchant[Merchant\Entity::EMAIL],
            'business_category'         => $merchant[Merchant\Entity::CATEGORY],
            'sales_team'                => $data[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][Entity::SALES_TEAM],
            'sales_poc_email'           => $spocEmail,
            'slot_booking_date_and_time'=> $slotBookingDateTime,
            'reviewer_name'             => $data['reviewer_name'],
            'pincode'                   => $data[Entity::PINCODE],
            'application_date'          => $dateTime,
            'green_channel'             => $green_channel_value,
        ];

        return array_merge($data,$skipDwtExpFields);
    }
}
