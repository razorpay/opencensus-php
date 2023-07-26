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
export const DISABLED_INSTRUMENT = [ZESTMONEY, GETSIMPL, FLEXIPAY];
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
