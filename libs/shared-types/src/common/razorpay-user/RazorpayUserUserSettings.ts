/**
 * Represents the user settings.
 */
export type RazorpayUserUserSettings = {
    /** Skip contact mobile verification setting */
    skip_contact_mobile_verify: string;
  
    /** Flag indicating if the home page was visited */
    rxHomevisitedFlag: string;
  
    /** Flag indicating if the CA apply request is done */
    clicked_ca_apply_request_done: string;
  
    /** Count of referral banner impressions */
    referral_banner_impression_count: string;
  };