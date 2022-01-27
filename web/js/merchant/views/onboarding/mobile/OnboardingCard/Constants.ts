export const POI_VERIFICATION_STATUS = {
  incorrect_details: {
    title: 'PAN Verification Failed',
    description:
      'Your PAN details did not match with the government PAN database. Please review and submit again.',
  },
  failed: {
    title: 'Unable to verify PAN',
    description:
      'Government’s PAN database seems to be down, we couldn’t verify your PAN Details. Please try again in a couple of minutes.',
  },
  pending: {
    title: 'Verifying your PAN details',
    description:
      'We are verifying your PAN details with the government PAN database this might take sometime. We will update you when it’s done.',
  },
  initiated: {
    title: 'Reviewing your KYC details',
    description:
      'We are reviewing your KYC details. We will notify once the details have been reviewed successfully',
  },
};

export const BANK_DETAILS_VERIFICATION_STATUS = {
  failed: {
    title: 'Bank Verification Failed',
    description: 'We were unable to verify your bank account. Please upload bank account proof.',
  },
};

export const ACTIVATION_STATUS_UNDER_REVIEW = {
  old_flow: {
    title: 'KYC details are under review',
    description:
      'Your documents are under review. It generally takes around 3 - 4 working days. Our team will reach out to you in case of any clarification',
  },
  new_flow: {
    title: 'Payment limits have been removed',
    description_with_payment_enable:
      'You can accept unlimited payments now. Settlements will be enabled after we successfully review your KYC details. It usually takes 3-4 working days. We will notify you if we require any clarifications on your KYC.',
    partial_match_title: 'Payments temporarily paused',
    partial_match_desc:
      'Our compliance team and banking partners are reviewing your KYC and your payments have been temporarily paused. We will review your KYC and reach out to you for any clarifications within 3-4 days.',
    post_nc_description:
      "Your documents and KYC detail are under review. It's now our responsibility to make sure your documents are processed. It usually takes 3-4 working days for our team to review your documents. We will reach out to you if we need any clarification.",
  },
};

export const ACTIVATION_STATUS_NEEDS_CLARIFICATION = {
  title: 'Clarification required',
  description: {
    normal:
      'We need some clarification regarding your submitted details. Please clarify these details at the earliest to get your account activated.',
    activated_mcc_pending:
      'We need some clarification regarding your submitted details. Update the required details in 1 day otherwise your settlements might get paused',
    funds_onhold:
      'We need some clarification regarding your submitted details. Please clarify these details to enable your settlements',
  },
};

export const ACTIVATION_STATUS_ACTIVATED = {
  old_title: 'Payments and Settlements have been enabled',
  title: 'KYC Verified Successfully',
  description:
    'Congratulations! Your KYC has been successfully verified and your account has been activated. Payments received by you will be settled to your account as per your settlement schedule.',
};

export const ACTIVATION_STATUS_REJECTED = {
  title: 'Account Rejected',
  old_title: 'Account Suspended',
  description:
    'We cant support your business because it doesnt meet our compliance requirements Please contact support to request for settlement of payments accepted via your account',
  old_description:
    'Due to irregularities in documents submitted by you, your account has been suspended. You will not be able to conduct live transactions',
};

export const ACTIVATION_STATUS_ACTIVATED_MCC_PENDING = {
  title: 'Payments and Settlements have been enabled',
  new_title: 'Payment and Settlements Enabled',
  description:
    'Congratulations! You can start accepting payments now. Payments will be settled to your bank account according to your settlement schedule. Please note that as part of the routine compliance checks mandated by our banking partners, we will review your business model, website details and reach out for further clarifications.',
  new_description:
    'Congratulations, now you can accept unlimited payments. Settlements to your bank account have been enabled. Please note that as part of routine compliance checks mandated by our banking partners, we may review your KYC again and reach out in case of further clarifications.',
  when_progress_bar_not_required:
    'Please note that as part of routine compliance checks mandated by our banking partners, we may review your KYC again and reach out in case of further clarifications.',
};

export const PAYMENT_ACTIVATED = {
  title: 'Just few more steps away from enabling settlements',
  description:
    'Congratulations! You are now all set and can start receiving payments from your customers up to INR 15,000. Complete your KYC Details to enable benefits like settlements and to extend this limit further!',
  limit_breach_desc:
    'Complete your KYC form to extend payment limits. Please note that your payments have been temporarily paused until you finish your KYC.',
};

export const DEDUPE = {
  title: 'Business Not Supported',
  description:
    "We can't support your business because it doesn't meet our compliance requirements If you think this is a mistake please reach out to our support.",
  L2_description:
    'In case you have pending settlements, you can raise a ticket and get your funds settled to your account.',
  old_title: 'Clarification required',
  old_description:
    'We need some clarification regarding your submitted details. Please contact support to provide clarification and activate your account',
};

export const ACTIVATION_PROGRESS = {
  title: 'You are just few steps away from enabling live payments',
  description: 'Submit a few KYC details and start accepting payments from your customers',
};

export const HARD_LIMIT_REACHED = {
  title: 'Settlements on hold',
  old_title: 'Account is Under Review',
  description:
    "Our compliance team and partner banks carry out routine audits of your KYC documents. We might temporarily pause your settlements during this time, but don't worry, just look for clarifications asked by our team on your registered email. Once we receive the clarifications, we will resume your settlements. Upon receiving your response, we will be able to process the application within 2 days and re enable settlements for you. Please note, you can still accept payments from your customers.",
};

export const GENERATE_TNC = {
  under_review: {
    old_title: 'Generate TnC',
    title: 'Payment limits removed, Generate TnC',
    payment_enable_desc:
      'You have submitted all the details. Please generate the terms and conditions at the earliest. Your review might get delayed in case of failure to do so. Review usually take 3-4 days.',
    partial_match_title: 'KYC under review, Generate TnC',
    partial_match_desc:
      'Our compliance team and banking partners are reviewing your KYC and your payments have been temporarily paused. We will reach out to you for any clarifications within 3-4 days. Meanwhile you can generate your Tnc page.',
    description:
      'You have submitted all the details. Please generate the terms and conditions at the earliest. Your review might get delayed in case of failure to do so. Review usually take 3-4 days.',
  },
  mcc_pending: {
    title: 'Payments have been enabled, Generate TnC to complete activation',
    description:
      'Your payments can now be settled to your bank account according to your settlement schedule. As part of the routine compliance checks mandated by our banking partners, we will review your kyc and reach out for further.',
    old_description_with_live:
      'Your account has been activated and you are in live mode now . Your payments will be settled to you according to your settlement schedule. Please generate Tnc at the earliest, failing which your settlements can be suspended.',
    old_description_with_test:
      'Your account has been activated. Your payments will be settled to you according to your settlement schedule. You can start accerpting payments by switching to live mode Please generate Tnc at the earliest, failing which your settlements can be suspended.',
  },
};

export const GREYLIST_STEP = {
  title: 'You are just few steps away from enabling live payments and settlements',
  description:
    'Submit a few more KYC details to accept payments from your customers and receive settlements in your account',
};
