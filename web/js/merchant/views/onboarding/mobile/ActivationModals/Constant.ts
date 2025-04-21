export const DEDUPE = {
  title: 'Business not supported',
  old_title: 'Clarification required',
  description:
    'Your current business category is not supported by our banking partners. If you wish to reconsider and update, please reach out to us via support.',
  L2_description: '',
  old_description:
    ' We need some clarification regarding your submitted details. Please contact support to activate your account.',
  buttonText: 'Contact Support',
};

export const POI_INITIATED = {
  title: 'Reviewing your KYC details',
  description:
    'We are verifying your KYC details. We will notify you once it has been reviewed successfully',
  buttonText: 'Okay, Got It',
};

export const PAYMENT_ENABLE = {
  title: 'Congratulations! You are ready to accept payments now.',
  description:
    'You are now all set and can start receiving payments from your customers up to INR 15,000. Complete your KYC Details to enable benefits like settlements and to extend this limit further!',
  sub_description: 'We have switched you to live mode, go ahead and accept your first payment!',
  buttonText: 'Start Accepting Payments',
  secondryButtonText: 'Complete KYC',
};

export const PAYMENT_DISABLE = {
  title: 'Few more details required',
  description:
    'We need a few more KYC details to enable payments and settlements for your business model.',
  buttonText: 'Complete KYC',
};

export const TNC = {
  title: 'KYC is under review Generate TnC now',
  partial_match_title: 'KYC is under review, payments have been temporarily paused',
  description:
    "Your documents and KYC detail are under review. It's now our responsibility to make sure your documents are processed. It usually takes 3-4 working days for our team to review your documents. We will reach out to you if we need any clarification.",
  partial_match_description:
    'Our compliance team and banking partners are reviewing your KYC and your payments have been temporarily paused. We will review your KYC and reach out to you for any clarifications within 3-4 days.',
  payment_enable_description:
    'Your payment limits have been removed and KYC is under review. KYC review process usually takes 3-4 working days. We will notify you if we require any clarifications on your KYC.',
  buttonText: 'Generate Terms And Conditions',
};

export const NC = {
  title: 'We need some clarification regarding some of your KYC details',
  description: {
    normal_nc: 'Please resolve some of the issues that we found with your uploaded KYC details',
    mcc_pending_nc:
      'Update required details within 1 day, otherwise your settlements might get paused.',
    onhold_nc: 'Please clarify some of your KYC details on web dashboard to enable settlements',
  },
  buttonText: 'Clarify Details Now',
  secondryButtonText: 'I’ll Do It Later',
};

export const REJECTED = {
  title: 'Account Rejected',
  description:
    "We can't support your business because it doesn't meet our compliance requirements Please contact support to request for settlement of any payments accepted via your account",
  buttonText: 'Contact Support',
};

export const UNDER_REVIEW = {
  title: 'KYC is under review',
  payment_enable_title: 'KYC is under review and payment limit has been removed',
  partial_match_title: 'KYC is under review, payments have been temporarily paused',
  description:
    "Your documents and KYC detail are under review. It's now our responsibility to make sure your documents are processed. We will reach out to you if we need any clarification. You may experience a delay.",
  partial_match_description:
    'Our compliance team and banking partners are reviewing your KYC and your payments have been temporarily paused. We will reach out to you for any clarifications. You may experience a delay.',
  payment_enable_description:
    'Your payment limits have been removed and KYC is under review. We will notify you if we require any clarifications on your KYC. You may experience a delay.',
  buttonText: 'Generate Terms And Conditions',
};

export const NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS = {
  title: 'We need a few more details to complete KYC verification',
  buttonText: 'Resolve now',
  pill: 'ACTION REQUIRED',
};

export const BDD_NEEDS_CLARIFICATION_WITH_PAYMENT_STATUS = {
  title: 'Action Required: Update Your KYC Details',
  buttonText: 'Resolve now',
  pill: 'ACTION REQUIRED',
};
