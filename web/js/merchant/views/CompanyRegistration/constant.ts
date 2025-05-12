import { BannerData, RizeJourneyType } from './types';
export const WORKFLOW_MILESTONES = {
  L1_MILESTONE: 'l1_milestone',
  L2_MILESTONE: 'l2_milestone',
};
// Workflow Components
export const WORKFLOW_COMPONENTS = {
  OVERVIEW: 'overview_component',
  RIZE_BUSINESS_TYPE: 'rize_business_type_component',
  RECOMMENDER_BUSINESS_TYPE: 'recommender_business_type_component',
  DO_NOT_SUPPORT: 'do_not_support_component',
  NEED_MORE_INFO: 'need_more_info_component',
  PRICING_DETAILS: 'pricing_details_component',
  DOCUMENT_SUMMARY: 'document_summary_component',
  ONBOARDING_SUMMARY: 'onboarding_summary_component',
  INCORPORATION_SUMMARY: 'incorporation_summary_component',
};
export const WORKFLOW_FIELDS = {
  DOCUMENT_FORM_APPROVED: 'document_form_approved',
  INCORPORATION_FORM_APPROVED: 'incorporation_form_approved',
  NAME_APPROVAL_STATUS: 'name_approval_status',
  INCORPORATION_STATUS: 'incorporation_status',
  DOCUMENT_LINK: 'document_link',
  POLLING_REQUIRED_EXTERNAL_FIELD: 'polling_required_external_field',
  REQUEST_CALLBACK_STATUS: 'request_callback_status',
};
export const POST_SALESFORCE_STEPS_STATUS = {
  COMPLETED: 'Completed',
  NEXT: 'Next',
  FINAL: 'Final',
  ONGOING: 'Ongoing',
} as const;

export const RIZE_JOURNEY = Object.freeze({
  INITIAL_SCREEN: 'initial_screen',
  RESUME_SCREEN: 'resume_screen',
  STATUS_SCREEN: 'status_screen',
  ACCOUNT_SCREEN: 'account_screen',
});

export const USER_JOURNEY = Object.freeze({
  basic_details_step: 'basic_details_step',
  business_details_step: 'business_details_step',
  payment_step: 'payment_step',
  company_name_submission_step: 'company_name_submission_step',
  document_upload_step: 'document_upload_step',
  post_payment_step: 'post_payment_step',
  post_incorporation_step: 'post_incorporation_step',
});

export const USER_JOURNEY_STEPS = [
  USER_JOURNEY.basic_details_step,
  USER_JOURNEY.business_details_step,
  USER_JOURNEY.payment_step,
];

export const UserJourneyProgress = [
  {
    screen: 'default',
    title: 'Sign Up',
  },
  {
    screen: USER_JOURNEY.basic_details_step,
    title: 'Basic Details',
  },
  {
    screen: USER_JOURNEY.business_details_step,
    title: 'Business Details',
  },
  {
    screen: USER_JOURNEY.payment_step,
    title: 'Payment',
  },
  {
    screen: USER_JOURNEY.company_name_submission_step,
    title: 'Company name submission',
  },
  {
    screen: USER_JOURNEY.document_upload_step,
    title: 'Document Upload',
  },
];

export const BANNER_DATA: Record<RizeJourneyType, BannerData> = {
  // Screen 1
  [RIZE_JOURNEY.INITIAL_SCREEN]: {
    main: {
      firstLine: 'Register your Business as a Private limited, LLP or OPC',
      secondLine: {
        subText: '',
        highlightedText: 'at just Rs. 1499 + Govt. Fees',
      },
    },
    midSection: {
      isIconContent: true,
    },
    button: {
      isButtonRequire: true,
      buttonText: 'Get Incorporated now',
    },
  },
  // Screen 2 - Resume Rize Incorporation  asdasasdas
  [RIZE_JOURNEY.RESUME_SCREEN]: {
    main: {
      firstLine: 'Your Company Registration is in progress',
      secondLine: {
        subText: '',
        highlightedText: '',
      },
    },
    midSection: {
      isIconContent: true,
    },
    button: {
      isButtonRequire: true,
      buttonText: 'Resume Company Registration',
    },
  },
  // Screen 3 - Check Rize Incorporation progress
  [RIZE_JOURNEY.STATUS_SCREEN]: {
    main: {
      firstLine: 'Your Company Registration is currently',
      secondLine: {
        subText: '',
        highlightedText: 'in progress',
      },
    },
    midSection: {
      isIconContent: false,
      text: 'Sit back & relax while we take care of the entire process',
    },
    button: {
      isButtonRequire: false,
    },
  },
  // Screen 4 - Account screen
  [RIZE_JOURNEY.ACCOUNT_SCREEN]: {
    main: {
      firstLine: 'Congratulations! 🎉',
      secondLine: {
        subText: 'Your company has been successfully registered.',
        highlightedText: '',
      },
    },
    button: {
      isButtonRequire: false, // TODO: hide it till we get download functionality from BE
      buttonText: 'Download Incorporation Documents',
    },
  },
};

export const HEADER_BENEFITS_OFFER = [
  'Trusted by 10,000+ Founders',
  'Hassle-free Company Registration',
];

export const PACKAGES = [
  'Company Registration',
  'MOA & AOA (If applicable)',
  'LLP Agreement (If applicable)',
  'Company PAN & TAN',
  'Incorporation Certificate',
  'Digital Signature Certificate (DSC)',
  'Company Name Approval',
  'Directors Identification Number (DIN)',
  'DSC Tokens, Support & Shipping',
];

export const MULTI_ACCOUNT_TITLE = 'Create a new account';
export const MULTI_ACCOUNT_SUBTITLE = 'Create a new account using existing or new credentials';
export const OFFERS_DATA = [
  {
    title: 'Legal Protection',
    description: 'Keep your personal assets safe if the business faces issues.',
  },
  {
    title: 'Automated Payroll with Razorpay X',
    description:
      'Access funding easier as most VCs, angel investors, etc. prefer registered businesses.',
  },
  {
    title: 'Tax Benefits & Government Schemes',
    description:
      'Get MSME benefits, Startup India perks, and claim business-related tax deductions.',
  },
];

export const RIZE_INCORPORATION =
  'https://easy.razorpay.com/rize/incorporation/onboarding?utm_source=direct&utm_medium=rize_razorpay_dashboard';

export const MULTI_ACCOUNT =
  'https://accounts.razorpay.com/merchants?auth_intent=signup&utm_source=rize_company_registration&utm_medium=rize_comapany_registration_cross_sell&utm_campaign=Rize%2Bcompany_reg%2Bpg_dashboard&redirecturl=https%3A%2F%2Feasy.razorpay.com%2Fonboarding%3Futm_source%3Drize_company_registration%26utm_medium%3Drize_comapany_registration_cross_sell%26utm_campaign%3DRize%252Bcompany_reg%252Bpg_dashboard';

export const EXCLUSIVE_OFFER_HEADER = 'Benefits of Company Registration in India';
export const ICORP_PACKAGE_HEADER = 'What’s included?';
export const RESUME_COMPANY_REG = 'Resume Company Registration';
export const TRACK_PROGRESS = 'Track Company Registration';

export const BUSSINESS_TYPE = {
  UNREGISTERED_TYPES: '11',
};

export const CONFIRMATION_HEADER = 'Ready to register your company?';
export const CONFIRMATION_SUBTEXT =
  "You're about to start your business registration journey as a Private Limited Company, LLP, or OPC with Razorpay.";
export const CONFIRMATION_FOOTER =
  'This service is exclusively for founders looking to register their business in India.';

export const DOCUMENT_FORM_STATUS = {
  not_submitted: 'not_submitted',
  submitted: 'submitted',
  approved: 'approved',
};
