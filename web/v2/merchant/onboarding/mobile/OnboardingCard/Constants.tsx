import React from 'react';
import Link from '@commander/shield/src/shared/Link';

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
};

export const BANK_DETAILS_VERIFICATION_STATUS = {
  failed: {
    title: 'Bank Verification Failed',
    description: 'We were unable to verify your bank account. Please upload bank account proof.',
  },
};

export const ACTIVATION_STATUS_UNDER_REVIEW = {
  registered: {
    title: 'Details are under review',
    description:
      'Your documents are under review. It generally takes around 3 - 5 working days. Our team will reach out to you in case of any clarification',
  },
};

export const ACTIVATION_STATUS_NEEDS_CLARIFICATION = {
  title: 'Clarification required',
  description:
    'We need some clarification regarding your submitted details. Please clarify these details at the earliest to get your account activated.',
};

export const ACTIVATION_STATUS_ACTIVATED = {
  title: 'Payments and Settlements have been enabled',
  description:
    'Your account has been activated. Your  payments will be settled to you according to your settlement schedule. Switch to live mode to start accepting payments',
  description_live_mode:
    'Your account has been activated. Your  payments will be settled to you according to your settlement schedule.',
};

export const ACTIVATION_STATUS_ACTIVATED_MCC_PENDING = {
  title: 'Payments and Settlements have been enabled',
  description:
    'You can start accepting payments in live mode now. Payments will be settled to your bank account according to your settlement schedule. Please note we may ask you for more clarifications later as part of routine checks.',
};

export const REMAINING_STEPS = {
  af_gl: {
    iaf_gl: {
      multiple: {
        title: 'You are just a few steps away from activating your account',
        description:
          'Submit the remaining details and start accepting domestic and international payments.',
      },
      single: {
        title: 'You are just one step away from activating your account.',
        description:
          'Submit the remaining details and start accepting domestic and international payments.',
      },
    },
    iaf_bl: {
      multiple: {
        title: 'You are just a few steps away from activating your account',
        description:
          'Submit the remaining details and start accepting domestic payments. International card payments may be restricted for your business model. Activate your account to know more.',
      },
      single: {
        title: 'You are just one step away from activating your account.',
        description:
          'Submit the remaining details and start accepting domestic payments. International card payments may be restricted for your business model. Activate your account to know more.',
      },
    },
  },
  af_wl: {
    iaf_wl: {
      enable_payments: {
        multiple: {
          title: 'You are just two steps away from enabling live payments',
          description:
            'Submit the remaining details and start accepting domestic and international payments.',
        },
        single: {
          title: 'You are just one step away from enabling live payments',
          description:
            'Submit the remaining details and start accepting domestic and international payments.',
        },
      },
      enable_settlements: {
        live_transaction_done: {
          multiple: {
            title: 'Just two steps away from enabling settlements',
            description:
              'Submit the bank details and the required documents and start recieving money in your bank account.',
          },
          single: {
            title: 'Just one step away from enabling settlements',
            description:
              'Upload the required documents and start recieving money in your bank account.',
          },
        },
        live_transaction_not_done: {
          multiple: {
            title:
              'Start accepting payments. Meanwhile we will await your details to enable settlements.',
            description:
              'Submit the bank details and the required documents and start recieving money in your bank account.',
          },
          single: {
            title:
              'Start accepting payments. Meanwhile we will await your details to enable settlements.',
            description:
              'Upload the required documents and start recieving money in your bank account.',
          },
        },
      },
    },
    iaf_gl: {
      enable_payments: {
        multiple: {
          title: 'You are just two steps away from enabling live payments',
          description:
            'Submit the remaining details and start accepting payments from your customers.',
        },
        single: {
          title: 'You are just one step away from enabling live payments',
          description:
            'Submit a few details and start accepting domestic payments. Complete account activation to request international payments.',
        },
      },
      enable_settlements: {
        multiple: {
          title:
            'Start accepting payments. Meanwhile we will await your details to enable settlements.',
          description:
            'Submit the bank details and the required documents and start recieving money in your bank account.',
        },
        single: {
          title:
            'Start accepting payments. Meanwhile we will await your details to enable settlements.',
          description:
            'Upload the required documents and start recieving money in your bank account.',
        },
      },
    },
  },
  unregistered: {
    enable_payments: {
      multiple: {
        title: 'You are just two steps away from enabling live payments',
        description:
          'Submit the remaining details and start accepting payments from your customers.',
      },
      single: {
        title: 'You are just one step away from enabling live payments',
        description:
          'Submit the remaining details and start accepting payments from your customers.',
      },
    },
    enable_settlements: {
      multiple: {
        title: 'You are just two steps away from enabling settlements',
        description:
          'Submit the bank details and the required documents and start recieving money in your bank account.',
      },
      single: {
        title: 'You are just one step away from enabling settlements',
        description:
          'Upload the required documents and start recieving money in your bank account.',
      },
    },
  },
};

export const DEDUPE = {
  title: 'Clarification required',
  description:
    'We need some clarification regarding your submitted details. Please contact support to provide clarification and activate your account',
};

export const ACTIVATION_PROGRESS = {
  title: 'You are just few steps away from enabling live payments',
  description: 'Submit the remaining details and start accepting payments from your customers',
};

export const HARD_LIMIT_REACHED = {
  title: 'Account is Under Review',
  description: (
    <>
      Our compliance team and partner banks carry out routine audits of your KYC documents. We might
      temporarily pause your settlements during this time, but don't worry, just look for
      clarifications asked by our team on your registered email. Once we receive the clarifications,
      we will resume your settlements. Upon receiving your response, we will be able to process the
      application within 2 days and re enable settlements for you. Please note, you can still accept
      payments from your customers.{' '}
      <Link
        href="https://knowledgebase.razorpay.com/support/solutions/articles/11000103841-why-is-my-settle[%E2%80%A6]ld-and-my-account-under-review-after-getting-activated"
        target="_blank"
      >
        More details
      </Link>
    </>
  ),
};
