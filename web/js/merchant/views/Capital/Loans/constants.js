import React from 'react';
import { REPAYMENT_STATUES } from '../CashAdvance/constants';

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

export const BUSINESS_NATURE_TYPES = [
  '',
  { label: 'Manufacturer', name: 'Manufacturer' },
  { label: 'Wholesaler', name: 'Wholesaler' },
  { label: 'Trader', name: 'Trader' },
  { label: 'Distributer', name: 'Distributer' },
  { label: 'Retailers', name: 'Retailers' },
  { label: 'Service Providers', name: 'Service Providers' },
];

export const PROPERTY_OWNERSHIP_TYPES = [
  '',
  { label: 'Owned', name: 'Owned' },
  { label: 'Rented', name: 'Rented' },
  { label: 'Other', name: 'Other' },
];

export const CAPITAL_LINKS = {
  faqs: 'https://razorpay.com/capital/working-capital-loans/#faqs',
  ca_faqs: 'https://razorpay.com/capital/cash-advance/#faqs',
  terms_and_conditions: 'https://razorpay.com/terms/',
  privacy_policy: 'https://razorpay.com/privacy/',
  capital_know_more: 'https://razorpay.com/capital/',
  check_credit_score: '/app/dashboard#creditscore',
  support_email: 'capital.support@razorpay.com',
};

export const APPLICATION_STATES = {
  CREATED: 'CREATED',
  PROMOTER_INFO_PENDING: 'PROMOTER_INFO_PENDING',
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
  // SLOT_SELECTION_PENDING: 'SLOT_SELECTION_PENDING',
  // DOCUMENT_COLLECTION_INITIATED: 'DOCUMENT_COLLECTION_INITIATED',
  OFFLINE_DOCUMENT_COLLECTION_PENDING: 'OFFLINE_DOCUMENT_COLLECTION_PENDING',
  // DOCUMENT_COLLECTION_FAILED: 'DOCUMENT_COLLECTION_FAILED',
  DOCUMENTS_UNDER_REVIEW: 'DOCUMENTS_UNDER_REVIEW',
  RZP_APPROVED: 'RZP_APPROVED',
  CREDIT_DISBURSED: 'CREDIT_DISBURSED',
  RZP_REJECTED: 'RZP_REJECTED',
  CLOSED: 'CLOSED',
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
  // APPLICATION_STATES.SLOT_SELECTION_PENDING,
  // APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
  APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
  // APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
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

export const PENDING_APPLICATION_STATES = [
  APPLICATION_STATES.PREVERIFICATION_IN_PROGRESS,
  APPLICATION_STATES.SCORE_GENERATION_PENDING,
  APPLICATION_STATES.CREDIT_OFFER_PENDING,
  APPLICATION_STATES.CONTRACT_PENDING,
  // APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
  // TODO: remove this
  APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING,
  APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW,
];

export const ERROR_STATES = [
  APPLICATION_STATES.PREVERIFICATION_FAILED,
  // APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED,
  APPLICATION_STATES.CREDIT_PULL_FAILED,
];

export const APPLICATION_DISABLED_STATES = {
  [APPLICATION_STATES.RZP_REJECTED]: {
    title: 'You are not eligible for',
    description:
      "Sorry, your application doesn't meet our lending partner's credit requirement at the moment. Suggestions to improve your business eligibility",
    tips: [
      'Maintain a good credit history',
      'Have a regular business cashflow',
      'Avoid bad debts & make EMI payments on time',
      'Application must be submited with major stakeholder details',
    ],
    subTitle: 'What’s SHOULD YOU DO Next',
    action_point:
      "Please submit a fresh application when you are ready. We'll be more than happy to serve you again.",
    ctaText: 'Apply for',
  },
  [APPLICATION_STATES.CLOSED]: {
    title: 'Your application is closed due to inactivity',
    description:
      "We haven't heard back from your since this application was created. Hence we have closed it.",
    subTitle: 'What’s SHOULD YOU DO Next',
    action_point:
      'Please submit a fresh application when you are ready and. We’d be would be happy to serve you again.',
    ctaText: 'Apply for',
  },
};

export const APPLICATION_STATE_DESCRIPTIONS = {
  BUSINESS_INFO_PENDING: {
    title: 'Check your loan eligibility',
    description: 'Complete your loan within a few minutes to check the loan offer',
    short_description: 'Business Info',
    ctaText: 'Start your application',
  },
  PROMOTER_INFO_PENDING: {
    title: 'Continue with your loan application...',
    description: 'Fill your details to check the loan eligibility and the loan offer',
    ctaText: 'Continue with your loan application',
    short_description: 'Promoter Info',
  },
  [APPLICATION_STATES.CREDIT_PULL_PENDING]: {
    title: 'Credit Report pending...',
    description: 'Fill your details to check the loan eligibility and the loan offer',
    ctaText: 'Check credit score',
    short_description: 'Credit Report',
    stages: {
      OTP_SCREEN: 'OTP Screen',
      CREDIT_REPORT: 'Credit Report',
    },
  },
  [APPLICATION_STATES.CREDIT_PULL_FAILED]: {
    title: 'Credit Report failed',
    description: 'Fill your details to check the loan eligibility and the loan offer',
    ctaText: 'view application',
    short_description: 'Credit Report',
  },
  [APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING]: {
    title: 'Upload your documents',
    description: 'Upload your documents for us to begin evaluation of your loan offer',
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
    description: 'We are evaluating your loan eligibility to calculate the loan offer',
    ctaText: 'view application',
    short_description: 'Evaluating Loan Offer',
  },
  [APPLICATION_STATES.PREVERIFICATION_FAILED]: {
    title: 'Document verification failed',
    description: `Reach out to ${CAPITAL_LINKS.support_email} for assistance`,
    ctaText: 'Upload Documents',
    short_description: 'Evaluating Loan Offer',
  },
  [APPLICATION_STATES.SCORE_GENERATION_PENDING]: {
    title: 'Evaluating loan offer...',
    description: 'We are evaluating your loan eligibility to calculate the loan offer',
    ctaText: 'view application',
    short_description: 'Evaluating Loan Offer',
  },
  [APPLICATION_STATES.CREDIT_OFFER_PENDING]: {
    title: 'Evaluating loan offer...',
    description: 'We are evaluating your loan eligibility to calculate the loan offer',
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
    description: 'Please sign the NACH to move forward with the loan disbursal process',
    ctaText: 'Upload Nach Form',
    short_description: 'Submit Nach Form',
  },
  [APPLICATION_STATES.NACH_UPLOAD_PENDING]: {
    title: 'Upload your NACH',
    description: 'Please sign the NACH to move forward with the loan disbursal process',
    ctaText: 'Upload Nach Form',
    short_description: 'Submit Nach Form',
  },
  // [APPLICATION_STATES.SLOT_SELECTION_PENDING]: {
  //   title: 'Document collection pending',
  //   description: 'Select a convenient time slot for document collection',
  //   ctaText: 'Schedule appointment',
  //   short_description: 'Schedule appointment',
  // },
  // [APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]: {
  //   title: 'Document collection pending',
  //   description: 'Our representative will reach out to you for the document collection',
  //   ctaText: 'view application',
  //   short_description: 'Document Collection',
  // },
  [APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING]: {
    title: 'Post-approval document collection',
    description:
      'Please share the final set of documents on this email: capital.support@razorpay.com',
    ctaText: 'view application',
    short_description: 'Document Collection',
  },
  // [APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]: {
  //   title: 'Documents collection failed',
  //   description: `Reach out to ${CAPITAL_LINKS.support_email} for assistance`,
  //   ctaText: 'view application',
  //   short_description: 'Documents Review',
  // },
  [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: {
    title: 'Documents review',
    description: 'Your documents will be shared with the lender for the final approval',
    ctaText: 'view',
    short_description: 'Documents Review',
  },
  [APPLICATION_STATES.RZP_APPROVED]: {
    title: 'Loan offer approved',
    description: 'Your documents have been shared with the lender for the final approval',
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

export const TENURE_UNIT_LABELS = {
  daily: ['day', 'days'],
  weekly: ['Week', 'Weeks'],
  fortnightly: ['Fortnight', 'Fortnights'],
  monthly: ['month', 'months'],
  months: ['month', 'months'],
  quarterly: ['Quarter', 'Quarters'],
  halfyearly: ['Halfyear', 'Halfyears'],
  yearly: ['Year', 'Years'],
};

export const CAPITAL_PRODUCT_CODES = {
  LOAN: 'LOAN',
  CASH_ADVANCE: 'LOC',
};

export const CAPITAL_PRODUCT_NAME_CODE_MAP = {
  loans: CAPITAL_PRODUCT_CODES.LOAN,
  'cash-advance': CAPITAL_PRODUCT_CODES.CASH_ADVANCE,
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
    "Indicative score representating an individual's ability to pay back the borrowed amount",
  ifsc_code: '11-digit code identifying your bank branch',
  disbursing_account: 'The future withdrawals will be disbursed to this bank account.',
  ca_tenure:
    'The period from the date of Cash advance approval to the date of closure of Cash Advance application.',
  ca_internal_credit_limit: 'The total amount that can be withdrawn at present.',
};

export const GENDER_OPTIONS = [
  {
    name: 'GENDER_TYPE_MALE',
    label: 'Male',
  },
  {
    name: 'GENDER_TYPE_FEMALE',
    label: 'Female',
  },
  {
    name: 'GENDER_TYPE_OTHERS',
    label: 'Others',
  },
];

export const GENDER_MAP = {
  GENDER_TYPE_MALE: 0,
  GENDER_TYPE_FEMALE: 1,
  GENDER_TYPE_OTHERS: 2,
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

export const APPLICATION_STATE_MESSAGE_MAP = {
  [APPLICATION_STATES.SCORE_GENERATION_PENDING]: (
    <span>
      The process takes around 24 hours. We will update you once all the parameters are verified and
      revert with the offer status.
    </span>
  ),
  CONTRACT_GENERATION_PENDING: (
    <span>
      The loan agreement will contain the commercials around the offer and the collection process.
      The process takes around 10-15 mins. Razorpay will update you once the agreement is ready to
      be signed through the mail.
    </span>
  ),
  // [APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]: (
  //   <span>
  //     Your loan application has been rejected due to the repeated failure of the document
  //     collection.
  //   </span>
  // ),
  [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: (
    <span>
      We usually confirm the document review within 1-2 working days. We’ll let you know once the
      Review is completed.
    </span>
  ),
};

export const APPLICATION_STATE_TITLE_MAP = {
  BUSINESS_INFO_PENDING: {
    title: 'Confirm your Business Details & Needs',
    description: 'Please enter your business details here',
  },
  BUSINESS_INFO_PENDING_LOCKED: {
    title: 'Confirm your Business Details & Needs',
    description: 'Sorry, Loan details cannot be modified after the loan offer is accepted',
    lockedNote: true,
  },
  PROMOTER_INFO_PENDING: {
    title: 'Tell us about your business owner',
    description: 'Help us with your business owner details to provide the best credit offer.',
  },
  PROMOTER_INFO_PENDING_LOCKED: {
    title: 'Tell us about your business owner',
    description: 'Sorry, You cannot edit the below information after Credit Enquiry is done',
    lockedNote: true,
  },
  MOBILE_VERIFICATION_PENDING: {
    title: 'OTP Verification for Credit Inquiry',
    description:
      'We will do a credit bureau pull based on your phone number and PAN to evaluate your credit score',
  },
  CREDIT_PULL_COMPLETED_WITH_NTC: {
    title: 'Credit Inquiry Report',
    type: 'conditional_success',
    description:
      "We couldn't find any credit records on your name. You may" +
      ' be still eligible for a loan.',
    rightComponent: (
      <div className="right-component">
        <span className="exp-logo-text">Powered by</span>
        <img className="exp-logo" src="https://cdn.razorpay.com/static/assets/experian_logo.png" />
      </div>
    ),
  },
  CREDIT_PULL_COMPLETED: {
    title: 'Credit Inquiry Report',
    description: 'This credit inquiry will not impact your credit score',
    rightComponent: (
      <div className="right-component">
        <span className="exp-logo-text">Powered by</span>
        <img className="exp-logo" src="https://cdn.razorpay.com/static/assets/experian_logo.png" />
      </div>
    ),
  },
  [APPLICATION_STATES.SCORE_GENERATION_PENDING]: {
    title: 'Evaluating Loan Offer',
    description: 'We will now review your documents and calculate the loan offer.',
    type: 'pending',
  },
  [APPLICATION_STATES.CREDIT_OFFER_PENDING]: {
    title: 'Evaluating Loan Offer',
    description: 'We will now review your documents and calculate the loan offer.',
    type: 'pending',
  },
  [APPLICATION_STATES.CREDIT_OFFER_GENERATED]: {
    title: 'Loan Offer',
    description: 'Accept the following loan offer to get the loan amount disbursed to your account',
  },
  CREDIT_OFFER_ACCEPTED: {
    title: 'Accepted Loan offer',
    description: 'Check the accepted loan offer details with repayment details here.',
    type: 'success',
  },
  CONTRACT_GENERATION_PENDING: {
    title: 'Loan Agreement is getting generated...',
    description:
      'We are generating the loan agreement with your loan offer details. Please wait for some moment.',
    type: 'pending',
  },
  [APPLICATION_STATES.CONTRACT_PENDING]: {
    title: 'E-Sign Loan Agreement',
    description: 'Please E-Sign the loan agreement by going to the leegality page.',
  },
  CONTRACT_SIGNED: {
    title: 'Loan Agreement',
    description:
      'Your loan agreement has been signed successfully. Check the loan agreement details below.',
    type: 'success',
  },
  [APPLICATION_STATES.NACH_UPLOAD_PENDING]: {
    title: 'Download & Submit a NACH form',
    description:
      "Why NACH? In case, there is a deficit in the collections flow, Razorpay holds the right to trigger the NACH to auto-debit the pending amount from the merchant's bank account.",
  },
  // [APPLICATION_STATES.SLOT_SELECTION_PENDING]: {
  //   title: 'Schedule an appointment for document collection',
  //   description:
  //     'Why? This is mandatory as the physical documents will be verified by the lender for processing the application and approving the final disbursal.',
  // },
  // [APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED]: {
  //   title: 'Document Collection',
  //   description:
  //     'Please be ready with the original documents along with a xerox copies. Our executive will be verifying the xerox copies with the original documents.',
  // },
  [APPLICATION_STATES.OFFLINE_DOCUMENT_COLLECTION_PENDING]: {
    title: 'Document Collection',
    description: (
      <span>
        Please <strong>Sign & Stamp</strong> the following documents and{' '}
        <strong>Send the copies</strong> to{' '}
        <a href={`mailto:${CAPITAL_LINKS.support_email}`}>{CAPITAL_LINKS.support_email}</a>
      </span>
    ),
  },
  // [APPLICATION_STATES.DOCUMENT_COLLECTION_FAILED]: {
  //   title: 'Document Collection Failed',
  //   description: 'Oops! It seems like we have not been able to collect your documents.',
  //   type: 'error',
  // },
  [APPLICATION_STATES.DOCUMENTS_UNDER_REVIEW]: {
    title: 'Documents Under Review',
    description: 'We’re reviewing your documents internally and with our vendor.',
    type: 'pending',
  },
  [`LOAN_${APPLICATION_STATES.RZP_APPROVED}`]: {
    title: 'Congratulations, Your loan has been approved!',
    description:
      'On a successful authorization, you will receive the' +
      ' following loan amount in your bank account',
    type: 'approval',
  },
  [`LOC_${APPLICATION_STATES.RZP_APPROVED}`]: {
    title: 'Congratulations, Cash Advance offer has been approved!',
    description: (
      <span>
        Check your approved line of credit offer details here. Any queries?{' '}
        <a href={`mailto:${CAPITAL_LINKS.support_email}`}>Write to us</a>
      </span>
    ),
    type: 'approval',
  },
  [APPLICATION_STATES.CREDIT_DISBURSED]: {
    title: 'Hurray! Disbursed Successfully',
    description: 'The following loan amount has been successfully disbursed to your bank account.',
    type: 'success',
  },
};

export const OFFLINE_COLLECTION_DOCUMENTS = {
  proprietorship: {
    personal: [
      {
        type: 'Applicant Selfie',
      },
      {
        type: 'Personal Proof Of Address',
        allowedDocuments: [
          'Aadhaar',
          'e-Aadhaar',
          'Passport',
          'Driving License',
          'Electricity bill in last 3 months if own property',
          'Rental agreement if rented property',
        ],
      },
      {
        type: 'Personal Proof Of Identification',
        allowedDocuments: ['Owner PAN', 'e-PAN'],
      },
    ],
    business: [
      {
        type: 'Business PAN',
      },
      {
        type: 'Any 1 of the following',
        list: [
          'GST certificate (if GST registered)',
          'MSME certificate',
          'IEC',
          'ITR with company name and income',
          'Shops and Establishment certificate',
        ],
      },
      {
        type: 'Address Proof with the business name in it',
        allowedDocuments: [
          'Electricity bill',
          'Any other Utility bill',
          'Current Account statement',
          'Rental Agreement',
        ],
      },
    ],
  },
  others: {
    personal: [
      {
        type: 'Applicant Selfie',
      },
      {
        type: 'Personal Proof Of Address',
        allowedDocuments: [
          'Aadhaar',
          'e-Aadhaar',
          'Passport',
          'Driving License',
          'Electricity bill in last 3 months if own property',
          'Rental agreement if rented property',
        ],
      },
      {
        type: 'Personal Proof Of Identification',
        allowedDocuments: ['Owner PAN', 'e-PAN'],
      },
    ],
    business: [
      {
        type: 'Any 1 of the following',
        list: [
          'GST certificate (if GST registered)',
          'MSME certificate',
          'IEC',
          'ITR with company name and income',
          'Shops and Establishment certificate',
        ],
      },
      {
        type: 'Address Proof with the business name in it',
        allowedDocuments: [
          'Electricity bill',
          'Any other Utility bill',
          'Current Account statement',
          'Rental Agreement',
        ],
      },
    ],
  },
};

export const GA_CATEGORY_BY_PRODUCT = {
  [CAPITAL_PRODUCT_CODES.CASH_ADVANCE]: 'Cash Advance - LOS',
  [CAPITAL_PRODUCT_CODES.LOAN]: 'Loans - LOS',
};

export const PREVERIFICATION_VIEW_STATES = {
  ACTIVE: 'ACTIVE',
  PROCESSING: 'PROCESSING',
  PROCESSED: 'PROCESSED',
  FEEDBACK: 'FEEDBACK',
};

export const NOOP = () => {};

export const PREVERIFICATION_FILE_UPLOAD_LIMIT = 7;

export const PREVERIFICATION_OPTIONS = {
  NETBANKING: 1,
  NATIVE_UPLOAD: 2,
};

export const PREVERIFICATION_NETBANKING_RETRY_LIMIT = 3;

export const AVAILABLE_FILE_TYPE_ICONS = ['pdf', 'jpg', 'png', 'csv', 'xlsx'];

export const LOANS_BASE_URL = '/capital/loans/';

export const LOANS_SECTIONS = {
  OVERVIEW: 'overview',
  REPAYMENTS_HISTORY: 'history',
};

export const DEFAULT_COUNT = 25;

export const getBreakupByBalanceType = (breakups, balanceType) => {
  return breakups
    .filter((breakup) => breakup.balance_type === balanceType)
    .reduce((total, currentBreakup) => {
      return total + Number(currentBreakup.breakup_amount);
    }, 0);
};

export const STATUS_LABELS = {
  [REPAYMENT_STATUES.STATUS_UNKNOWN]: 'Unknown',
  [REPAYMENT_STATUES.STATUS_PENDING]: 'Pending',
  [REPAYMENT_STATUES.STATUS_COLLECTED]: 'Repaid',
  [REPAYMENT_STATUES.STATUS_SETTLED]: 'Repaid',
  [REPAYMENT_STATUES.STATUS_FAILED]: 'Failed',
};

export const StatusPillClasses = {
  [REPAYMENT_STATUES.STATUS_UNKNOWN]: 'bg-light-2',
  [REPAYMENT_STATUES.STATUS_PENDING]: 'bg-warning-2',
  [REPAYMENT_STATUES.STATUS_COLLECTED]: 'bg-success-2',
  [REPAYMENT_STATUES.STATUS_SETTLED]: 'bg-success-2',
  [REPAYMENT_STATUES.STATUS_FAILED]: 'bg-danger-2',
};

export const REPAYMENT_FILTER_STATUS_OPTIONS = {
  [REPAYMENT_STATUES.STATUS_COLLECTED]: STATUS_LABELS[[REPAYMENT_STATUES.STATUS_COLLECTED]],
  [REPAYMENT_STATUES.STATUS_FAILED]: STATUS_LABELS[[REPAYMENT_STATUES.STATUS_FAILED]],
};
