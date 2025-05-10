<?php

namespace RZP\Models\Partner\KycAccessState;

class Constants
{
    const TOKEN_EXPIRY_TIME = 172800; //48 hours

    const MAX_REJECTION_COUNT = 3;

    const UPSERT_KYC_ACCESS_STATE = 'upsert_kyc_access_state';

    const PAYLOAD = 'payload';

    const CREATE_CONSENT = 'create_consent';


    const PARTNER_KYC_ACCESS_CONSENT_FOR_RESELLER = 'PartnerKYCAccessConsent';

    const PARTNER_KYC_ACCESS_CONSENT_FOR_AGGREGATOR = 'PartnerDashboardAccessConsent';

    const L2_CONSENT_EVENT = 'L2';
}
