export const REQUESTED = 'requested';
export const PENDING = 'pending';
export const ACTIVATED = 'activated';
export const REJECTED = 'rejected';
export const ACTION_REQUIRED = 'action_required';
export const ACTIVATED_ACTION_REQUIRED = 'activated_action_required';
export const REQUESTABLE = 'requestable';
export const ACCOUNT_LINKABLE = 'account_linkable';
export const CANCELLED = 'cancelled';
export const GREYED = 'greyed';
export const REINITATED = 'reinitiated';

export const WEBSITE_DETAILS = 'website_details';
export const MERCHANT_DOCUMENTS = 'merchant_documents';
export const MERCHANT_DETAILS = 'merchant_details';
export const ZESTMONEY = 'ZestMoney';
export const GETSIMPL = 'Simpl';
export const FLEXIPAY = 'Flexipay';
export const PAYTM = 'Paytm';
export const MAESTRO = 'Maestro';
export const STATE_BANK_OF_INDIA = 'State Bank of India';
export const BANK_OF_BAHRAIN_AND_KUWAIT = 'Bank of Bahrain and Kuwait';
export const BASSEIN_CATHOLIC_CO_OPERATIVE_BANK = 'Bassein Catholic Co-operative Bank';
export const COSMOS_CO_OPERATIVE_BANK = 'Cosmos Co-operative Bank';
export const ESAF_SMALL_FINANCE_BANK = 'ESAF Small Finance Bank';
export const JANTA_SAHAKARI_BANK_PUNE = 'Janata Sahakari Bank (Pune)';
export const KALUPUR_COMMERCIAL_CO_OPERATIVE_BANK = 'Kalupur Commercial Co-operative Bank';
export const MEHSANA_URBAN_CO_OPERATIVE_BANK = 'Mehsana Urban Co-operative Bank';
export const NKGSB_CO_OPERATIVE_BANK = 'NKGSB Co-operative Bank';
export const NORTH_EAST_SMALL_FINANCE_BANK = 'North East Small Finance Bank';
export const STATE_BANK_OF_BIKANER_AND_JAIPUR = 'State Bank of Bikaner and Jaipur';
export const STATE_BANK_OF_HYERADABAD = 'State Bank of Hyderabad';
export const STATE_BANK_OF_PATIALA = 'State Bank of Patiala';
export const STATE_BANK_OF_TRAVANCORE = 'State Bank of Travancore';
export const SURYODAY_SMALL_FINANACE_BANK = 'Suryoday Small Finance Bank';
export const TAMILNADU_STATE_APEX_CO_OPERATIVE_BANK = 'Tamilnadu State Apex Co-operative Bank';
export const THANE_BHARAT_SAHAKARI_BANK = 'Thane Bharat Sahakari Bank';
export const THANE_JANATA_SAHAKARI_BANK = 'Thane Janata Sahakari Bank';
export const VARACHHA_CO_OPERATIVE_BANK = 'Varachha Co-operative Bank';
export const ZOROASTRIAN_CO_OPERATIVE_BANK = 'Zoroastrian Co-operative Bank';
export const PHONEPE = 'Phonepe';
export const PAYPAL = 'Paypal';
export const TRUSTLY = 'Trustly';
export const POLI = 'POLI';
export const GIROPAY = 'Giropay';
export const SOFORT = 'Sofort';
export const STATE_BANK_OF_MYSORE = 'State Bank of Mysore';

export const DISABLED_INSTRUMENT = [
  ZESTMONEY,
  GETSIMPL,
  FLEXIPAY,
  PAYTM,
  MAESTRO,
  STATE_BANK_OF_INDIA,
  BANK_OF_BAHRAIN_AND_KUWAIT,
  COSMOS_CO_OPERATIVE_BANK,
  JANTA_SAHAKARI_BANK_PUNE,
  MEHSANA_URBAN_CO_OPERATIVE_BANK,
  NKGSB_CO_OPERATIVE_BANK,
  NORTH_EAST_SMALL_FINANCE_BANK,
  STATE_BANK_OF_BIKANER_AND_JAIPUR,
  STATE_BANK_OF_HYERADABAD,
  STATE_BANK_OF_MYSORE,
  STATE_BANK_OF_PATIALA,
  STATE_BANK_OF_TRAVANCORE,
  SURYODAY_SMALL_FINANACE_BANK,
  TAMILNADU_STATE_APEX_CO_OPERATIVE_BANK,
  THANE_BHARAT_SAHAKARI_BANK,
  THANE_JANATA_SAHAKARI_BANK,
  VARACHHA_CO_OPERATIVE_BANK,
  ZOROASTRIAN_CO_OPERATIVE_BANK,
  PHONEPE,
  PAYPAL,
  TRUSTLY,
  POLI,
  GIROPAY,
  SOFORT,
  ESAF_SMALL_FINANCE_BANK,
  KALUPUR_COMMERCIAL_CO_OPERATIVE_BANK,
];

export const DEACTIVATED = 'deactivated';

export const statusClass = {
  Request: 'btn btn-primary',
  account_linkable: 'btn btn-primary',
  requestable: 'btn btn-primary',
  cancelled: 'btn btn-primary',
  activated: 'activated status',
  updated: 'activated status',
  requested: 'requested status',
  pending: 'pending status',
  rejected: 'rejected status',
  action_required: 'action-required status',
  under_review: 'action-required under-review status',
  activated_action_required: 'activated-action-required status',
  greyed: 'btn btn-primary disabled',
  reinitiated: 'requested status',
};

export const statusPopoverText = {
  activated: 'Payment method active on your checkout',
  requested: 'Payment method has been requested',
  reinitiated: 'Payment method has been reinitiated',
  pending: 'Your request has been forwarded for approval',
  rejected: 'Your request has been rejected',
  action_required: 'Action required on your end to complete the process',
  activated_action_required: 'Payment method active on your checkout',
  under_review: 'Information provided is under review',
  updated: 'Information provided is updated',
};

export const additionalDetailsStatus = {
  open: 'under_review',
  approved: 'under_review',
  rejected: 'rejected',
  failed: 'rejected',
  executed: 'updated',
  closed: 'updated',
};

export const STANDARD_PRICING_URL = 'https://razorpay.com/pricing/';
