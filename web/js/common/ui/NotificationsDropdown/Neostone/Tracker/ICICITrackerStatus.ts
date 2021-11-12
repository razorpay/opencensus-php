import { APIResponseType } from '../TypeDeclare/XCATypeDeclare';

export const ICICI_STATUS = [
  {
    bankStatus: 'recieved',
    uiStatus: 'Telephonic verification',
    subText:
      'Our executive will connect with you soon to verify the details. In the meantime, keep your documents ready for faster account opening.',
    code: 500,
    highlight: false,
  },
  {
    bankStatus: 'sent_to_bank',
    uiStatus: 'In-person verification & documents pick up',
    subText:
      'Our banking partner will contact you soon for in-person verification. Please keep your documents ready for a faster verification process.',
    code: 500,
    highlight: false,
  },
  {
    bankStatus: 'account_opened',
    uiStatus: 'Account activation',
    subText: 'You are just one step away from exploring the future of banking!',
    code: 500,
    highlight: false,
  },
];

export const ICICI_ACTIVATE = [
  {
    bankStatus: 'account_opened',
    uiStatus: 'Start your Journey! ✨',
    subText: 'You are just one step away from exploring the future of banking!',
    code: 200,
    highlight: true,
  },
  {
    bankStatus: 'registration_request_sent',
    uiStatus: 'Start your Journey! ✨',
    subText: 'You are just one step away from exploring the future of banking!',
    code: 200,
    highlight: true,
  },
  {
    bankStatus: 'account_activated',
    uiStatus: 'Start your Journey! ✨',
    subText: 'Your current account is now active, and you’re ready to take off!',
    code: 200,
    highlight: true,
  },
];

export const ICICI_PAN_OR_APP = [
  {
    bankStatus: 'pending',
    uiStatus: 'Complete your application',
    subText: 'Complete your online KYC to fast track your current account application. ',
    code: 300,
    highlight: true,
  },
  {
    bankStatus: 'panInitiated',
    uiStatus: 'PAN verification in progress',
    subText:
      'We’re verifying your PAN details. Takes about 5-10 minutes to complete. Please contact us if it takes longer.',
    code: 300,
    highlight: true,
  },
  {
    bankStatus: 'panFailed',
    uiStatus: 'PAN verification failed',
    subText: 'Please enter a valid PAN number that is registered with your business to submit KYC.',
    code: 400,
    highlight: true,
  },
];

export const currentAccountStatuses = {
  created: 'created',
  picked: 'picked',
  initiated: 'initiated',
  processing: 'processing',
  processed: 'processed',
  cancelled: 'cancelled',
  activated: 'activated',
  unserviceable: 'unserviceable',
  rejected: 'rejected',
  archived: 'archived',
};
export const caApplicationBlockedICICIStatus: Array<APIResponseType> = [
  currentAccountStatuses.unserviceable,
  currentAccountStatuses.cancelled,
  currentAccountStatuses.rejected,
  currentAccountStatuses.archived,
];

export const ICICIKYCStatus = {
  created: 'created',
  sent_to_bank: 'sent_to_bank',
  user_submitted: 'user_submitted',
  bank_processing: 'bank_processing',
  account_opened: 'account_opened',
  registration_request_sent: 'registration_request_sent',
  account_activated: 'account_activated',
};
export const PAN_VERIFICATION_STATUSES = {
  initiated: 'initiated',
  verified: 'verified',
  failed: 'failed',
  not_matched: 'not_matched',
  incorrect_details: 'incorrect_details',
  unavailable: 'unavailable',
};

export const panVerificationFailedStatuses = [
  PAN_VERIFICATION_STATUSES.failed,
  PAN_VERIFICATION_STATUSES.incorrect_details,
  PAN_VERIFICATION_STATUSES.not_matched,
];
