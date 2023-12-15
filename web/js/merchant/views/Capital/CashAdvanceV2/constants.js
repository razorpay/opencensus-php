import { CAPITAL_PRODUCT_CODES } from 'merchant/views/Capital/Loans/constants';

export const CASH_ADVANCE_ADVANTAGES = [
  {
    title: '1st withdrawal is free',
    subTitle: 'Get upto 100% Interest cashback on your first withdrawal (Upto ₹50,000).',
    imagePath: `${window.cdnBaseUrl}/static/assets/cash-advance/firstWithrawal.svg`,
    altText: 'First Witdhrawal',
  },
  {
    title: 'Withdraw Cash Instantly',
    subTitle:
      'Get upto 10 lakh credit limit and once enabled, withdraw when you need without any additional application',
    imagePath: `${window.cdnBaseUrl}/static/assets/cash-advance/instantWithdrawal.svg`,
    altText: 'Withdraw Cash Instantly',
  },
  {
    title: 'Pay only on your use',
    subTitle:
      'Only pay interest on the amount you withdraw and when you withdraw. There are no hidden charges.',
    imagePath: `${window.cdnBaseUrl}/static/assets/cash-advance/payOwnUse.svg`,
    altText: 'Pay only on your use',
  },
  {
    title: 'Partial Repayments',
    subTitle:
      'You can choose to repay total due on a single day or split it throughout the repayment period',
    imagePath: `${window.cdnBaseUrl}/static/assets/cash-advance/partialPayment.svg`,
    altText: 'Partial Repayments',
  },
];

export const COMMON_APPLICATION_STATES = {
  BUSINESS_DETAILS_PENDING: 'BUSINESS_DETAILS_PENDING',
  PERSONAL_DETAILS_PENDING: 'PERSONAL_DETAILS_PENDING',
  CREDIT_PULL_FAILED: 'CREDIT_PULL_FAILED',
  CREDIT_PULL_PENDING: 'CREDIT_PULL_PENDING',
  CREDIT_OFFER_PENDING: 'CREDIT_OFFER_PENDING',
  CREDIT_OFFER_GENERATED: 'CREDIT_OFFER_GENERATED',
  OFFLINE_DOCUMENT_COLLECTION_PENDING: 'OFFLINE_DOCUMENT_COLLECTION_PENDING',
  CLOSED: 'CLOSED',
  RZP_APPROVED: 'RZP_APPROVED',
  RZP_REJECTED: 'RZP_REJECTED',
  STATE_CREATED: 'STATE_CREATED',
  STATE_CLOSED: 'STATE_CLOSED',
  STATE_COMPLETED: 'STATE_COMPLETED',
  STATE_REJECTED: 'STATE_REJECTED',
};

export const APPLICATION_STATES = {
  ...COMMON_APPLICATION_STATES,
  // CREDIT_REQUIRED: 'CREDIT_REQUIRED',
  PREVERIFICATION_UPLOAD_PENDING: 'PREVERIFICATION_UPLOAD_PENDING',
  PREVERFICATION_FAILED: 'PREVERFICATION_FAILED',
  PREVERIFICATION_IN_PROGRESS: 'PREVERIFICATION_IN_PROGRESS',
  PREVERIFICATION_FAILED: 'PREVERIFICATION_FAILED',
  SCORE_GENERATION_PENDING: 'SCORE_GENERATION_PENDING',
  DOCUMENTS_UNDER_REVIEW: 'DOCUMENTS_UNDER_REVIEW',
  ESIGN_PENDING: 'ESIGN_PENDING',
  ESIGN_EXPIRED: 'ESIGN_EXPIRED',
};

export const APPLICATION_STATE_SEQUENCE = [
  // APPLICATION_STATES.CREDIT_REQUIRED,
  APPLICATION_STATES.BUSINESS_DETAILS_PENDING,
  APPLICATION_STATES.PERSONAL_DETAILS_PENDING,
  APPLICATION_STATES.CREDIT_PULL_PENDING,
  APPLICATION_STATES.CREDIT_PULL_FAILED,
  APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
  APPLICATION_STATES.PREVERFICATION_FAILED,
  APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
  APPLICATION_STATES.PREVERIFICATION_FAILED,
  APPLICATION_STATES.SCORE_GENERATION_PENDING,
  APPLICATION_STATES.CREDIT_OFFER_PENDING,
  APPLICATION_STATES.CREDIT_OFFER_GENERATED,
  APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
  // APPLICATION_STATES.ESIGN_PENDING,
  // APPLICATION_STATES.ESIGN_EXPIRED,
  APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
  APPLICATION_STATES.STATE_COMPLETED,
];

export const APPLICATION_STATE_SEQUENCE_STAGES = {
  STAGE_1: [
    // APPLICATION_STATES.CREDIT_REQUIRED,
    APPLICATION_STATES.BUSINESS_DETAILS_PENDING,
    APPLICATION_STATES.PERSONAL_DETAILS_PENDING,
    APPLICATION_STATES.CREDIT_PULL_PENDING,
    APPLICATION_STATES.CREDIT_PULL_FAILED,
    APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
    APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
    APPLICATION_STATES.PREVERIFICATION_FAILED,
    APPLICATION_STATES.SCORE_GENERATION_PENDING,
    APPLICATION_STATES.CREDIT_OFFER_PENDING,
  ],
  STAGE_2: [APPLICATION_STATES.CREDIT_OFFER_GENERATED],
  STAGE_3: [
    APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
    // APPLICATION_STATES.ESIGN_PENDING,
    // APPLICATION_STATES.ESIGN_EXPIRED,
    APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
    APPLICATION_STATES.STATE_COMPLETED,
    APPLICATION_STATES.STATE_REJECTED,
    APPLICATION_STATES.CLOSED,
  ],
};

export const APPLICATION_NAVIGATION_CONFIG = [
  {
    parentName: 'Check Eligibility',
    stageName: 'STAGE_1',
    actionButtonText: 'Continue Application',
    subText: 'Share business details to evaluate your application',
    subSteps: [
      {
        stepName: 'Business Details',
        step: APPLICATION_STATES.BUSINESS_DETAILS_PENDING,
      },
      {
        stepName: "Owner's Details",
        step: APPLICATION_STATES.PERSONAL_DETAILS_PENDING,
      },
      {
        stepName: 'Credit Verification',
        step: APPLICATION_STATES.CREDIT_PULL_PENDING,
      },
      {
        stepName: 'Bank Statement',
        step: APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
      },
    ],
  },
  {
    parentName: 'Credit Offer',
    stageName: 'STAGE_2',
    subText: 'Review and Accept your credit offer',
    actionButtonText: 'View Offer',
  },
  {
    parentName: 'KYC Documents',
    stageName: 'STAGE_3',
    subText: 'Post offer, submit supporting documents to start withdrawing',
    actionButtonText: 'Upload Documents',
  },
];

export const FINAL_STEP_CONFIG = [
  {
    parentName: 'Hurray! Withdrawal activated. 🎉',
    stageName: 'STAGE_4',
    subText: 'Your credit line is now active.',
    actionButtonText: 'Start Withdrawing',
  },
];
export const CREDIT_REPORT_SCORES = {
  UPPER: 900,
  LOWER: 300,
  ACCEPTANCE_THRESHOLD: 450,
};

export const DOCUMENT_STATUSES = {
  UPLOAD_PENDING: 'UPLOAD_PENDING',
  PROCESSING: 'PROCESSING',
  APPROVAL_PENDING: 'APPROVAL_PENDING',
  APPROVED: 'APPROVED',
  FAILED: 'FAILED',
  SKIPPED: 'SKIPPED',
};

const REJECTED_CONFIG = {
  title: 'You are not eligible',
  description:
    "Sorry, your application doesn't meet our lending partner's credit requirement at the moment. Suggestions to improve your business eligibility",
  tips: [
    'Maintain a good credit history',
    'Have a regular business cashflow',
    'Avoid bad debts & make EMI payments on time',
    'Application must be submited with major stakeholder details',
  ],
  subTitle: 'What SHOULD YOU DO Next',
  actionPoint:
    "Please submit a fresh application when you are ready. We'll be more than happy to serve you again.",
  ctaText: 'Apply for',
};

export const APPLICATION_DISABLED_STATES = {
  [COMMON_APPLICATION_STATES.RZP_REJECTED]: REJECTED_CONFIG,
  [COMMON_APPLICATION_STATES.STATE_REJECTED]: REJECTED_CONFIG,
  [COMMON_APPLICATION_STATES.STATE_CLOSED]: {
    title: 'Your application is closed due to inactivity',
    description:
      "We haven't heard back from your since this application was created. Hence we have closed it.",
    subTitle: 'What SHOULD YOU DO Next',
    actionPoint:
      'Please submit a fresh application when you are ready and. We’d be would be happy to serve you again.',
    ctaText: 'Apply for',
  },
};

export const STEP_NAMES = {
  pending: 'pendingStep',
  active: 'currentStep',
  success: 'completedStep',
  locked: 'lockedStep',
};

export const INITIAL_APPLICATION_STATE = {
  application: null,
  applicantId: null,
  applicant: null,
  navigation: {
    current: null,
    applicationStatus: null,
  },
  creditOffers: [],
};

export const SIGNATORY_STATUS = {
  PENDING: 'PENDING',
  SIGNED: 'SIGNED',
  FAILED: 'FAILED',
  COMPLETED: 'COMPLETED',
};

export const APPLICATION_NOT_SUBMITTED = 'record not found';

export const CASH_ADVANCE_LINK =
  'https://x.razorpay.com/capital/cash-advance/application/?from=dashboard';

export const NEW_CASH_ADVANCE_DASHBOARD =
  'https://x.razorpay.com/capital/cash-advance/?from=dashboard';

export const PRODUCT_CONFIG = {
  [CAPITAL_PRODUCT_CODES.CASH_ADVANCE]: {
    faqUrl: 'https://razorpay.com/knowledgebase/#merchant',
    applicationUrl: CASH_ADVANCE_LINK,
    dashboardUrl: '/capital/cash-advance/withdrawals',
    content: {
      heading: 'Cash Advance',
      title: 'Your Cash Advance Application',
      subheading:
        'Get additional money whenever required, repay and borrow again up to your limit any number of times.',
    },
  },
  [CAPITAL_PRODUCT_CODES.LOC_EMI]: {
    faqUrl: 'https://razorpay.com/x/line-of-credit/#faqs',
    applicationUrl:
      'https://x.razorpay.com/capital/line-of-credit/application/?intent=capital_loc_emi&from=dashboard',
    dashboardUrl: 'https://x.razorpay.com/capital/line-of-credit/?from=dashboard',
    content: {
      heading: 'Line of Credit',
      title: 'Your Line of Credit Application',
      subheading: `Get additional money whenever required, repay and borrow again up to your limit any number of times.`,
    },
  },
};
