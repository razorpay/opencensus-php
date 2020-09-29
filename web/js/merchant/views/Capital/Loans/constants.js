export const BUSINESS_TYPES = {
  1: 'PROPRIETORSHIP',
  2: 'INDIVIDUAL',
  3: 'PARTNERSHIP',
  4: 'PRIVATE',
  5: 'PUBLIC',
  6: 'LLP',
  7: 'NGO',
  9: 'TRUST',
  10: 'SOCIETY',
  11: 'NOT_REGISTERED',
};

export const APPLICATION_STATES = {
  CREATED: 'CREATED',
  CREDIT_PULL_PENDING: 'CREDIT_PULL_PENDING',
  CREDIT_PULL_FAILED: 'CREDIT_PULL_FAILED',
  PREVERIFICATION_UPLOAD_PENDING: 'PREVERIFICATION_UPLOAD_PENDING',
  PREVERIFICATION_IN_PROGRESS: 'PREVERIFICATION_IN_PROGRESS',
  PREVERIFICATION_FAILED: 'PREVERIFICATION_FAILED',
  SCORE_GENERATION_PENDING: 'SCORE_GENERATION_PENDING',
  CREDIT_OFFER_PENDING: 'CREDIT_OFFER_PENDING',
  CREDIT_OFFER_GENERATED: 'CREDIT_OFFER_GENERATED',
  CONTRACT_PENDING: 'CONTRACT_PENDING',
  NACH_CREATION_PENDING: 'NACH_CREATION_PENDING',
  NACH_UPLOAD_PENDING: 'NACH_UPLOAD_PENDING',
  SLOT_SELECTION_PENDING: 'SLOT_SELECTION_PENDING',
  DOCUMENT_COLLECTION_INITIATED: 'DOCUMENT_COLLECTION_INITIATED',
  OFFLINE_DOCUMENT_COLLECTION_PENDING: 'OFFLINE_DOCUMENT_COLLECTION_PENDING',
  DOCUMENT_COLLECTION_FAILED: 'DOCUMENT_COLLECTION_FAILED',
  DOCUMENTS_UNDER_REVIEW: 'DOCUMENTS_UNDER_REVIEW',
  RZP_APPROVED: 'RZP_APPROVED',
  CREDIT_DISBURSED: 'CREDIT_DISBURSED',
};

export const APPLICATION_STATE_SEQUENCE = [
  'BUSINESS_INFO_PENDING',
  'PROMOTER_INFO_PENDING',
  APPLICATION_STATES.CREDIT_PULL_PENDING,
  APPLICATION_STATES.CREDIT_PULL_FAILED,
  APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
  APPLICATION_STATES.PREVERIFICATION_FAILED,
  APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
  APPLICATION_STATES.SCORE_GENERATION_PENDING,
  APPLICATION_STATES.CREDIT_OFFER_PENDING,
  APPLICATION_STATES.CREDIT_OFFER_GENERATED,
  APPLICATION_STATES.CONTRACT_PENDING,
  APPLICATION_STATES.NACH_CREATION_PENDING,
  APPLICATION_STATES.NACH_UPLOAD_PENDING,
  APPLICATION_STATES.SLOT_SELECTION_PENDING,
  APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
  APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
  APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
  APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
  APPLICATION_STATES.RZP_APPROVED,
  APPLICATION_STATES.CREDIT_DISBURSED,
];

export const CONSOLIDATED_STATES = {
  CHECK_LOAN_ELIGIBILITY: 'CHECK_LOAN_ELIGIBILITY',
  LOAN_APPLICATION: 'LOAN_APPLICATION',
  DOCUMENT_COLLECTION: 'DOCUMENT_COLLECTION',
  FINAL_REVIEW: 'FINAL_REVIEW',
  FUND_DISBURSED: 'FUND_DISBURSED',
};

export const CONSOLIDATED_STATE_SEQUENCE = [
  'CHECK_LOAN_ELIGIBILITY',
  'LOAN_APPLICATION',
  'DOCUMENT_COLLECTION',
  'FINAL_REVIEW',
  'FUND_DISBURSED',
];

export const APPLICATION_STATE_GROUPS = {
  CHECK_LOAN_ELIGIBILITY: [
    'BUSINESS_INFO_PENDING',
    'PROMOTER_INFO_PENDING',
    APPLICATION_STATES.CREDIT_PULL_PENDING,
    APPLICATION_STATES.CREDIT_PULL_FAILED,
    APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
    APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
    APPLICATION_STATES.PREVERIFICATION_FAILED,
    APPLICATION_STATES.SCORE_GENERATION_PENDING,
    APPLICATION_STATES.CREDIT_OFFER_PENDING,
  ],
  LOAN_APPLICATION: [
    APPLICATION_STATES.CREDIT_OFFER_GENERATED,
    APPLICATION_STATES.CONTRACT_PENDING,
    APPLICATION_STATES.NACH_CREATION_PENDING,
    APPLICATION_STATES.NACH_UPLOAD_PENDING,
  ],
  DOCUMENT_COLLECTION: [
    APPLICATION_STATES.SLOT_SELECTION_PENDING,
    APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
    APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
  ],
  FINAL_REVIEW: [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW, APPLICATION_STATES.RZP_APPROVED],
  FUND_DISBURSED: [APPLICATION_STATES.CREDIT_DISBURSED],
};

export const PENDING_APPLICATION_STATES = [
  APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
  APPLICATION_STATES.SCORE_GENERATION_PENDING,
  APPLICATION_STATES.CREDIT_OFFER_PENDING,
  APPLICATION_STATES.CONTRACT_PENDING,
  APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
  // TODO: remove this
  APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
  APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
];

export const ERROR_STATES = [
  APPLICATION_STATES.PREVERIFICATION_FAILED,
  APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
  APPLICATION_STATES.CREDIT_PULL_FAILED,
];

export const APPLICATION_STATE_DESCRIPTIONS = {
  BUSINESS_INFO_PENDING: {
    title: 'Check your loan eligibility',
    description: 'Complete your loan within a few minutes to check the loan' + ' offer',
    short_description: 'Business Info',
    ctaText: 'Start your application',
  },
  PROMOTER_INFO_PENDING: {
    title: 'Continue with your loan application...',
    description: 'Fill your details to check the loan eligibility and the' + ' loan offer',
    ctaText: 'Continue with your loan application',
    short_description: 'Promoter Info',
  },
  [APPLICATION_STATES.CREDIT_PULL_PENDING]: {
    title: 'Credit Report pending...',
    description: 'Fill your details to check the loan eligibility and the' + ' loan offer',
    ctaText: 'Check credit score',
    short_description: 'Credit Report',
    stages: {
      OTP_SCREEN: 'OTP Screen',
      CREDIT_REPORT: 'Credit Report',
    },
  },
  [APPLICATION_STATES.CREDIT_PULL_FAILED]: {
    title: 'Credit Report failed',
    description: 'Fill your details to check the loan eligibility and the' + ' loan offer',
    ctaText: 'view application',
    short_description: 'Credit Report',
  },
  [APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING]: {
    title: 'Upload your documents',
    description: 'Upload your documents for us to begin evaluation of your' + ' loan offer',
    ctaText: 'Upload documents',
    short_description: 'Documents Upload',
    stages: {
      ADDRESS_PROOF: 'Address Proof',
      BUSINESS_DOCS: 'Business Docs',
      BANK_STATEMENT: 'Bank Statements',
    },
  },
  [APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS]: {
    title: 'Evaluating loan offer...',
    description: 'We are evaluating your loan eligibility to calculate the' + ' loan offer',
    ctaText: 'view application',
    short_description: 'Evaluating Loan Offer',
  },
  [APPLICATION_STATES.PREVERIFICATION_FAILED]: {
    title: 'Document verification failed',
    description: 'Reach out to capital.support@razorpay for assistance',
    ctaText: 'Upload Documents',
    short_description: 'Evaluating Loan Offer',
  },
  [APPLICATION_STATES.SCORE_GENERATION_PENDING]: {
    title: 'Evaluating loan offer...',
    description: 'We are evaluating your loan eligibility to calculate the' + ' loan offer',
    ctaText: 'view application',
    short_description: 'Evaluating Loan Offer',
  },
  [APPLICATION_STATES.CREDIT_OFFER_PENDING]: {
    title: 'Evaluating loan offer...',
    description: 'We are evaluating your loan eligibility to calculate the' + ' loan offer',
    ctaText: 'view application',
    short_description: 'Loan Offer',
  },
  [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: {
    title: 'Loan offer generated',
    description: 'We have the loan offer ready for you. Accept the terms to move ahead',
    ctaText: 'View Loan Offer',
    short_description: 'Loan Offer',
  },
  [APPLICATION_STATES.CONTRACT_PENDING]: {
    title: 'Sign your agreement',
    description: 'Sign the loan offer terms to move forward with disbursal',
    ctaText: 'Sign Loan Agreement',
    short_description: 'Loan Agreement',
  },
  [APPLICATION_STATES.NACH_CREATION_PENDING]: {
    title: 'Upload your NACH',
    description: 'Please sign the NACH to move forward with the loan' + ' disbursal process',
    ctaText: 'Upload Nach Form',
    short_description: 'Submit Nach Form',
  },
  [APPLICATION_STATES.NACH_UPLOAD_PENDING]: {
    title: 'Upload your NACH',
    description: 'Please sign the NACH to move forward with the loan' + ' disbursal process',
    ctaText: 'Upload Nach Form',
    short_description: 'Submit Nach Form',
  },
  [APPLICATION_STATES.SLOT_SELECTION_PENDING]: {
    title: 'Document collection pending',
    description: 'Select a convenient time slot for document collection',
    ctaText: 'Schedule appointment',
    short_description: 'Schedule appointment',
  },
  [APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]: {
    title: 'Document collection pending',
    description: 'Our representative will reach out to you for the document' + ' collection',
    ctaText: 'view application',
    short_description: 'Document Collection',
  },
  [APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING]: {
    title: 'Document collection pending',
    description: 'Our representative will reach out to you for the document' + ' collection',
    ctaText: 'view application',
    short_description: 'Document Collection',
  },
  [APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]: {
    title: 'Documents collection failed',
    description: 'Reach out to capital.support@razorpay for assitance',
    ctaText: 'view application',
    short_description: 'Documents Review',
  },
  [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: {
    title: 'Documents review',
    description: 'Your documents will be shared with the lender for the' + ' final approval',
    ctaText: 'view',
    short_description: 'Documents Review',
  },
  [APPLICATION_STATES.RZP_APPROVED]: {
    title: 'Loan offer approved',
    description: 'Your documents have been shared with the lender for the' + ' final approval',
    ctaText: 'View Loan offer',
    short_description: 'Loan approval',
  },
  [APPLICATION_STATES.CREDIT_DISBURSED]: {
    title: 'Loan disbursal',
    description: 'Loan amount will be disbursed in your bank account',
    ctaText: 'View Disbursal details',
    short_description: 'Disbursal details',
  },
};

export const STATE_GROUP_COMPLETION_DESCRIPTION = {
  [CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY]: {
    title: 'Check loan eligibility',
    description: 'Loan eligibility check is' + ' successful and loan offer has been calculated',
  },
  [CONSOLIDATED_STATES.LOAN_APPLICATION]: {
    title: 'Complete loan application',
    description: 'You have successfully completed the Loan application form',
  },
  [CONSOLIDATED_STATES.DOCUMENT_COLLECTION]: {
    title: 'Document collection is done',
    description: 'Our executive has successfully picked up your Documents and proofs',
  },
  [CONSOLIDATED_STATES.FINAL_REVIEW]: {
    title: 'Loan offer is Approved!',
    description: 'We’ve successfully reviewed documents and approved your loan offer',
  },
  [CONSOLIDATED_STATES.FUND_DISBURSED]: {
    title: 'Fund Disbursed!',
    description: 'The loan amount has been successfully disbursed to your bank account.',
  },
};

export const SIDE_NAVIGATION_STATE_GROUPS = {
  [CONSOLIDATED_STATES.CHECK_LOAN_ELIGIBILITY]: {
    steps: {
      BUSINESS_INFO_PENDING: ['BUSINESS_INFO_PENDING'],
      PROMOTER_INFO_PENDING: ['CREATED', 'PROMOTER_INFO_PENDING'],
      [APPLICATION_STATES.CREDIT_PULL_PENDING]: [
        APPLICATION_STATES.CREDIT_PULL_PENDING,
        APPLICATION_STATES.CREDIT_PULL_FAILED,
      ],
      [APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING]: [
        APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING,
        APPLICATION_STATES.PREVERIFICATION_FAILED,
      ],
      [APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS]: [
        APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
        APPLICATION_STATES.SCORE_GENERATION_PENDING,
        APPLICATION_STATES.CREDIT_OFFER_PENDING,
      ],
    },
    description: 'Check loan eligibility',
    index: 0,
  },
  [CONSOLIDATED_STATES.LOAN_APPLICATION]: {
    steps: {
      [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: [APPLICATION_STATES.CREDIT_OFFER_GENERATED],
      // [APPLICATION_STATES.CONTRACT_PENDING]: [
      //   APPLICATION_STATES.CONTRACT_PENDING,
      // ],
      [APPLICATION_STATES.NACH_UPLOAD_PENDING]: [
        APPLICATION_STATES.NACH_CREATION_PENDING,
        APPLICATION_STATES.NACH_UPLOAD_PENDING,
      ],
    },
    description: 'Complete application',
    index: 1,
  },
  [CONSOLIDATED_STATES.DOCUMENT_COLLECTION]: {
    steps: {
      [APPLICATION_STATES.SLOT_SELECTION_PENDING]: [APPLICATION_STATES.SLOT_SELECTION_PENDING],
      [APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]: [
        APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
        APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
      ],
    },
    description: 'Document Collection',
    index: 2,
  },
  [CONSOLIDATED_STATES.FINAL_REVIEW]: {
    steps: {
      [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW],
      [APPLICATION_STATES.RZP_APPROVED]: [APPLICATION_STATES.RZP_APPROVED],
    },
    description: 'Final Review',
    index: 3,
  },
  [CONSOLIDATED_STATES.FUND_DISBURSED]: {
    steps: {
      [APPLICATION_STATES.CREDIT_DISBURSED]: [APPLICATION_STATES.CREDIT_DISBURSED],
    },
    description: 'Fund Disbursement',
    index: 4,
  },
};

export const TENURE_UNIT_LABELS = {
  daily: ['day', 'days'],
  weekly: ['Week', 'Weeks'],
  fortnightly: ['Fortnight', 'Fortnights'],
  monthly: ['month', 'months'],
  quarterly: ['Quarter', 'Quarters'],
  halfyearly: ['Halfyear', 'Halfyears'],
  yearly: ['Year', 'Years'],
};

export const TOOLTIP_DESCRIPTIONS = {
  ewi: 'Amount to be repaid on a weekly basis.',
  daily_repayable_amount:
    'Amount to be repaid on a daily basis. This will be deducted from your transaction volume.',
  edi: 'Amount to be repaid on a daily basis. This will be deducted from your transaction volume.',
  tenure: 'Time period within which you will repay the loan amount with the interest.',
  loan_agreement_faq:
    'The loan agreement will contain the commercials around the offer and the collection process',
  nach_sign_faq:
    'The loan agreement contains the commercials around the offer and the collection process',
  credit_score:
    "Indicative score representating an individual's ability to" + ' pay back the borrowed amount',
  ifsc_code: '11-digit code identifying your bank branch',
};

export const CAPITAL_LINKS = {
  faqs: 'https://razorpay.com/capital/working-capital-loans/#faqs',
  terms_and_conditions: 'https://razorpay.com/terms/',
  privacy_policy: 'https://razorpay.com/privacy/',
  capital_know_more: 'https://razorpay.com/capital/',
  check_credit_score: '/app/dashboard#creditscore',
};

export const GENDER_MAP = {
  GENDER_TYPE_MALE: 0,
  GENDER_TYPE_FEMALE: 1,
  GENDER_TYPE_OTHER: 2,
};

export const DOCUMENT_GROUP_NAMES_MAP = {
  business_registration_proof: 'Business Registration Proof',
  credit_bureau_report: 'Credit Bureau Report',
  business_identification_proof: 'Business Pan',
  proof_of_address: 'Proof Of Address',
  proof_of_identification: 'Owner Pan',
  income_proof: 'Bank Account Statement',
};

export const VERIFICATION_TIME_SLOTS = [
  {
    text: '8 AM - 12 PM',
    value: 'RZP_SLOT_1',
  },
  {
    text: '12 PM - 4 PM',
    value: 'RZP_SLOT_2',
  },
  {
    text: '4 PM - 8 PM',
    value: 'RZP_SLOT_3',
  },
];

export const HOTJAR_TRIGGERS = {
  LOAN_APPLICATION_PAGE_OPEN: 'Loans_page_open',
  LOAN_APPLICATION_OPEN: 'Loans_Application_Open',
  LOANS_BUSINESS_INFO: 'Loans_Business_Info',
  LOANS_CREDIT_INQUIRY: 'Loans_Credit_Inquiry',
  LOANS_DOCUMENT_UPLOAD: 'Loans_Document_Upload',
  LOANS_LOAN_OFFER: 'Loans_Loan_Offer',
  LOANS_SUBMIT_NACH: 'Loans_Submit_Nach',
  LOANS_APPOINTMENT_SCHEDULE: 'Loans_Appointment_Schedule',
  LOANS_FINAL_APPROVAL: 'Loans_Final_Approval',
  LOAN_FUND_DISBURSED: 'Loan_Fund_Disbursed',
  LOAN_REPAYMENT_PAGE: 'Loan_Repayment_Page',
};
