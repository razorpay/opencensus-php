import React from 'react';
import { getActiveStatusOnly } from '.';

const OVERALL_STATUS_MSGS = [
  {
    uiStatus: 'Account linking initiated',
  },
  {
    uiStatus: 'Documents pickup',
  },
  {
    uiStatus: 'Verification process',
  },
  {
    uiStatus: 'Account activation',
  },
];

const helpText = {
  check_back_in_30_minutes:
    'If you’ve already completed self approval, please check back in 30 minutes. Still need help? Contact us.',
  need_help: 'Need help',
};

const helpTicketIDs = {
  tickets: 'tickets',
};

const ICICI_LA_STATUSES = {
  account_linking_initiated: 'account_linking_initiated',
  credentials_verification_failed: 'credentials_verification_failed',
  registration_request_sent: 'registration_request_sent',
  registration_request_sent_and_completed: 'registration_request_sent_and_completed',
  registration_request_sent_and_failed: 'registration_request_sent_and_failed',
  contacted_by_sales_and_registration_request_sent:
    'contacted_by_sales_and_registration_request_sent',
  contacted_by_sales_and_registration_failed: 'contacted_by_sales_and_registration_failed',
  contacted_by_sales_and_registration_completed: 'contacted_by_sales_and_registration_completed',
  documents_signed_and_registration_request_sent: 'documents_signed_and_registration_request_sent',
  documents_signed_and_registration_failed: 'documents_signed_and_registration_failed',
  documents_signed_and_registration_completed: 'documents_signed_and_registration_completed',
  sent_to_bank_and_registration_request_sent: 'sent_to_bank_and_registration_request_sent',
  sent_to_bank_and_registration_failed: 'sent_to_bank_and_registration_failed',
  sent_to_bank_and_registration_completed: 'sent_to_bank_and_registration_completed',
  documents_picked_up_and_registration_request_sent:
    'documents_picked_up_and_registration_request_sent',
  documents_picked_up_and_registration_failed: 'documents_picked_up_and_registration_failed',
  documents_picked_up_and_registration_completed: 'documents_picked_up_and_registration_completed',
  discrepancy_in_documents_and_registration_request_sent:
    'discrepancy_in_documents_and_registration_request_sent',
  discrepancy_in_documents_and_registration_completed:
    'discrepancy_in_documents_and_registration_completed',
  discrepancy_in_documents_and_registration_failed:
    'discrepancy_in_documents_and_registration_failed',
  stp_mapping_completed_and_registration_request_sent:
    'stp_mapping_completed_and_registration_request_sent',
  stp_mapping_completed_and_registration_failed: 'stp_mapping_completed_and_registration_failed',
  stp_mapping_completed_and_registration_completed:
    'stp_mapping_completed_and_registration_completed',
  account_number_validated: 'account_number_validated',
  account_number_validation_failed: 'account_number_validation_failed',
  account_activated: 'account_activated',
};

const STATUS_TITLES = {
  get_started_on_razorpayx: 'Get started on RazorpayX',
  continue_onboarding: 'Continue Onboarding',
  documents_pickup: 'Documents pickup',
  verification_in_progress: 'Verification in progress',
  account_activation_pending: 'Account activation pending',
  activate_now: 'Activate now',
  start_your_journey: 'Start your Journey! ✨',
};

const CTA_LABELS = {
  get_started: 'Get Started',
  continue: 'Continue',
  view_docs: 'View Docs',
  finish_self_approval: 'Finish Self Approval',
  activate_your_account: 'Activate Your Account',
  activate_now: 'Activate now',
};

const CTA_TYPES = {
  primary: 'primary',
  grayed: 'grayed',
};

const CALinkingUserStatuses = {
  SELF_APPROVAL_DONE: 'SELF_APPROVAL_DONE',
  DOCUMENTS_DOWNLOADED: 'DOCUMENTS_DOWNLOADED',
};

const CA_LINKING_URLS = {
  account_details: `${window.bankingServiceUrl}/current-account-linking/account-details`,
  verification_docs: `${window.bankingServiceUrl}/current-account-linking/verification-docs`,
  self_approval: `${window.bankingServiceUrl}/current-account-linking/self-approval`,
  account_activation: `${window.bankingServiceUrl}/current-account-linking/account-activation`,
};

const ACTIVE_STATUS_MSGS = [
  {
    bankStatus: [
      ICICI_LA_STATUSES.discrepancy_in_documents_and_registration_request_sent,
      ICICI_LA_STATUSES.discrepancy_in_documents_and_registration_completed,
      ICICI_LA_STATUSES.discrepancy_in_documents_and_registration_failed,
    ],
    uiStatus: STATUS_TITLES.account_activation_pending,
    subText:
      'It looks like there were some discrepanices in the documents you submitted. ICICI executive will connect with you soon to share more details and re-initiate document pick up.',
    code: null,
    highlight: false,
    cta: {
      label: CTA_LABELS.view_docs,
      url: CA_LINKING_URLS.verification_docs,
      type: CTA_TYPES.grayed,
      component: 'iframe',
    },
    verticalStep: 3,
    helpData: {
      helpText: helpText.need_help,
      helpTicketID: helpTicketIDs.tickets,
    },
  },
  {
    bankStatus: [
      ICICI_LA_STATUSES.stp_mapping_completed_and_registration_request_sent,
      ICICI_LA_STATUSES.stp_mapping_completed_and_registration_failed,
    ],
    uiStatus: STATUS_TITLES.account_activation_pending,
    subText:
      'It looks like your self approval is still pending. Please follow the steps and complete this process for faster account activation',
    code: null,
    highlight: false,
    cta: {
      label: CTA_LABELS.finish_self_approval,
      url: CA_LINKING_URLS.self_approval,
      type: CTA_TYPES.primary,
      component: 'iframe',
    },
    helpData: {
      helpText: helpText.check_back_in_30_minutes,
      helpTicketID: helpTicketIDs.tickets,
    },
    verticalStep: 3,
  },
  {
    bankStatus: [
      ICICI_LA_STATUSES.stp_mapping_completed_and_registration_completed,
      ICICI_LA_STATUSES.account_number_validated,
    ],
    uiStatus: STATUS_TITLES.verification_in_progress,
    subText:
      'We are reviewing your documents and self approval process. Once completed, we will notify you.',
    code: null,
    highlight: false,
    verticalStep: 2,
  },
  {
    bankStatus: ICICI_LA_STATUSES.account_number_validation_failed,
    uiStatus: STATUS_TITLES.account_activation_pending,
    subText:
      'It looks like your account number or IFSC code was invalid. Please enter correct account details to start transacting on RazorpayX today!',
    code: null,
    highlight: false,
    cta: {
      label: CTA_LABELS.activate_now,
      url: CA_LINKING_URLS.account_activation,
      type: CTA_TYPES.primary,
      component: 'iframe',
    },
    helpData: {
      helpText: helpText.need_help,
      helpTicketID: helpTicketIDs.tickets,
    },
    verticalStep: 3,
  },
  {
    bankStatus: ICICI_LA_STATUSES.account_activated,
    uiStatus: STATUS_TITLES.start_your_journey,
    subText: (
      <>
        We have reviewed your documents and self approval process ⚡ <br />
        <strong>Unlock 300 free payouts</strong> and start transacting on RazorpayX instantly!
      </>
    ),
    code: null,
    highlight: false,
    verticalStep: 3,
  },
];

const BLOCKED_STATUSES = [
  ICICI_LA_STATUSES.discrepancy_in_documents_and_registration_request_sent,
  ICICI_LA_STATUSES.discrepancy_in_documents_and_registration_completed,
  ICICI_LA_STATUSES.discrepancy_in_documents_and_registration_failed,
  ICICI_LA_STATUSES.stp_mapping_completed_and_registration_request_sent,
  ICICI_LA_STATUSES.stp_mapping_completed_and_registration_failed,
  ICICI_LA_STATUSES.account_number_validation_failed,
];

const getICICILAStatusData = (
  combinedStatus: string,
  caLinkingUserStatus: string | undefined | null,
): Record<string, unknown> => {
  let statusData: any = {};

  if (
    combinedStatus === ICICI_LA_STATUSES.account_linking_initiated ||
    combinedStatus === ICICI_LA_STATUSES.credentials_verification_failed
  ) {
    statusData = {
      bankStatus: [
        ICICI_LA_STATUSES.account_linking_initiated,
        ICICI_LA_STATUSES.credentials_verification_failed,
      ],
      uiStatus: STATUS_TITLES.continue_onboarding,
      subText: (
        <>
          Unlock the future of banking with your existing bank account ⚡️ It only{' '}
          <strong>takes less than 10 mins</strong>
        </>
      ),
      cta: {
        label: CTA_LABELS.continue,
        url: CA_LINKING_URLS.account_details,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 0,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.registration_request_sent &&
    !caLinkingUserStatus
  ) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.registration_request_sent,
      uiStatus: STATUS_TITLES.continue_onboarding,
      subText:
        'Only 2 steps away! Download the documents required for verification for your account activation on RazorpayX',
      cta: {
        label: CTA_LABELS.continue,
        url: CA_LINKING_URLS.verification_docs,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 0,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.registration_request_sent &&
    caLinkingUserStatus &&
    caLinkingUserStatus !== CALinkingUserStatuses.SELF_APPROVAL_DONE
  ) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.registration_request_sent,
      uiStatus: STATUS_TITLES.continue_onboarding,
      subText:
        'Complete your self approval for a faster account activation process. It will take less than 5 mins!',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 0,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.registration_request_sent_and_completed ||
    (combinedStatus === ICICI_LA_STATUSES.registration_request_sent &&
      caLinkingUserStatus === CALinkingUserStatuses.SELF_APPROVAL_DONE)
  ) {
    statusData = {
      bankStatus: [
        ICICI_LA_STATUSES.registration_request_sent,
        ICICI_LA_STATUSES.registration_request_sent,
      ],
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon to pick up your signed documents.',
      cta: {
        label: CTA_LABELS.view_docs,
        url: CA_LINKING_URLS.verification_docs,
        type: CTA_TYPES.grayed,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (combinedStatus === ICICI_LA_STATUSES.registration_request_sent_and_failed) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.registration_request_sent_and_failed,
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon for pick up your signed documents. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.contacted_by_sales_and_registration_request_sent &&
    caLinkingUserStatus !== CALinkingUserStatuses.SELF_APPROVAL_DONE
  ) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.contacted_by_sales_and_registration_request_sent,
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon for pick up your signed documents. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (combinedStatus === ICICI_LA_STATUSES.contacted_by_sales_and_registration_failed) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.contacted_by_sales_and_registration_request_sent,
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon for pick up your signed documents. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.contacted_by_sales_and_registration_completed ||
    (combinedStatus === ICICI_LA_STATUSES.contacted_by_sales_and_registration_request_sent &&
      caLinkingUserStatus === CALinkingUserStatuses.SELF_APPROVAL_DONE)
  ) {
    statusData = {
      bankStatus: [
        ICICI_LA_STATUSES.contacted_by_sales_and_registration_completed,
        ICICI_LA_STATUSES.contacted_by_sales_and_registration_request_sent,
      ],
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon to pick up your signed documents.',
      cta: {
        label: CTA_LABELS.view_docs,
        url: CA_LINKING_URLS.verification_docs,
        type: CTA_TYPES.grayed,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.documents_signed_and_registration_request_sent &&
    caLinkingUserStatus !== CALinkingUserStatuses.SELF_APPROVAL_DONE
  ) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.documents_signed_and_registration_request_sent,
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon for pick up your signed documents. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (combinedStatus === ICICI_LA_STATUSES.documents_signed_and_registration_failed) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.documents_signed_and_registration_failed,
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon for pick up your signed documents. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.documents_signed_and_registration_completed ||
    (combinedStatus === ICICI_LA_STATUSES.documents_signed_and_registration_request_sent &&
      caLinkingUserStatus === CALinkingUserStatuses.SELF_APPROVAL_DONE)
  ) {
    statusData = {
      bankStatus: [
        ICICI_LA_STATUSES.documents_signed_and_registration_completed,
        ICICI_LA_STATUSES.documents_signed_and_registration_request_sent,
      ],
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon to pick up your signed documents.',
      cta: {
        label: CTA_LABELS.view_docs,
        url: CA_LINKING_URLS.verification_docs,
        type: CTA_TYPES.grayed,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.sent_to_bank_and_registration_request_sent &&
    caLinkingUserStatus !== CALinkingUserStatuses.SELF_APPROVAL_DONE
  ) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.sent_to_bank_and_registration_request_sent,
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon for pick up your signed documents. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (combinedStatus === ICICI_LA_STATUSES.sent_to_bank_and_registration_failed) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.sent_to_bank_and_registration_failed,
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon for pick up your signed documents. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.sent_to_bank_and_registration_completed ||
    (combinedStatus === ICICI_LA_STATUSES.sent_to_bank_and_registration_request_sent &&
      caLinkingUserStatus === CALinkingUserStatuses.SELF_APPROVAL_DONE)
  ) {
    statusData = {
      bankStatus: [
        ICICI_LA_STATUSES.sent_to_bank_and_registration_completed,
        ICICI_LA_STATUSES.sent_to_bank_and_registration_request_sent,
      ],
      uiStatus: STATUS_TITLES.documents_pickup,
      subText:
        'An executive from ICICI Bank will connect with you soon to pick up your signed documents.',
      cta: {
        label: CTA_LABELS.view_docs,
        url: CA_LINKING_URLS.verification_docs,
        type: CTA_TYPES.grayed,
        component: 'iframe',
      },
      verticalStep: 1,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.documents_picked_up_and_registration_request_sent &&
    caLinkingUserStatus !== CALinkingUserStatuses.SELF_APPROVAL_DONE
  ) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.documents_picked_up_and_registration_request_sent,
      uiStatus: STATUS_TITLES.verification_in_progress,
      subText:
        'We are reviewing your documents. Once completed, we will notify you. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 2,
    };
  } else if (combinedStatus === ICICI_LA_STATUSES.documents_picked_up_and_registration_failed) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.documents_picked_up_and_registration_failed,
      uiStatus: STATUS_TITLES.verification_in_progress,
      subText:
        'We are reviewing your documents. Once completed, we will notify you. Meanwhile, please complete your self approval process to avoid any delays in account activation.',
      cta: {
        label: CTA_LABELS.finish_self_approval,
        url: CA_LINKING_URLS.self_approval,
        type: CTA_TYPES.primary,
        component: 'iframe',
      },
      verticalStep: 2,
    };
  } else if (
    combinedStatus === ICICI_LA_STATUSES.documents_picked_up_and_registration_completed ||
    (combinedStatus === ICICI_LA_STATUSES.documents_picked_up_and_registration_request_sent &&
      caLinkingUserStatus === CALinkingUserStatuses.SELF_APPROVAL_DONE)
  ) {
    statusData = {
      bankStatus: ICICI_LA_STATUSES.documents_picked_up_and_registration_failed,
      uiStatus: STATUS_TITLES.verification_in_progress,
      subText:
        'We are reviewing your documents and self approval process. Once completed, we will notify you.',
      verticalStep: 2,
    };
  } else {
    const activeState = getActiveStatusOnly(combinedStatus, ACTIVE_STATUS_MSGS);
    statusData = activeState.length ? activeState[0] : {};
    // statusData = ACTIVE_STATUS_MSGS.find(({ bankStatus }) => bankStatus === combinedStatus);
  }

  const statusCode = combinedStatus === ICICI_LA_STATUSES.account_activated ? 600 : 300;
  Object.assign(statusData, { code: statusCode, highlight: true });
  return statusData;
};

const VERTICAL_STEP_MARKERS = [
  ICICI_LA_STATUSES.registration_request_sent_and_completed,
  ICICI_LA_STATUSES.documents_picked_up_and_registration_failed,
  ICICI_LA_STATUSES.account_number_validation_failed,
];

export {
  OVERALL_STATUS_MSGS,
  ACTIVE_STATUS_MSGS,
  ICICI_LA_STATUSES,
  VERTICAL_STEP_MARKERS,
  BLOCKED_STATUSES,
  getICICILAStatusData,
};
