import {
  PolicyPageCreationFormFieldType,
  SuggestionSteps,
  ValidationState,
  WebsitePolicyPages,
  WebsitePolicyPagesDetailsKeys,
} from '../types';

export const PolicyPagesSuggestionsList = {
  [SuggestionSteps.POLICY_PAGES]: [
    'Terms and Conditions',
    'Privacy Policy',
    'Shipping Policy',
    'Contact Us',
    'Cancellation and Refunds',
  ],
  [SuggestionSteps.MISSING_POLICY_PAGES_terms]: [
    'Contact information',
    'Effective date for policy',
    'Limitation of liability and disclaimer of warranties',
    'Rules of conduct',
    'User restrictions',
  ],
  [SuggestionSteps.MISSING_POLICY_PAGES_privacy]: [
    'What personal information you collect from your website/app users',
    'How you collect the information',
    'How you use any collected information',
    'How you keep information safe',
    'Clauses on information sharing with any third parties',
  ],
  [SuggestionSteps.MISSING_POLICY_PAGES_contact]: [
    'Email address',
    'Mobile number(if available)',
    'Operating address (where you conduct day-to-day business from)',
  ],
  [SuggestionSteps.MISSING_POLICY_PAGES_refund]: [
    'When things can be returned or exchanged (like 30, 60, or 90 days past purchase date)',
    'How to initiate a return or exchange (like, an email address to contact)',
    'What is your refund processing time (like 7, 15, or 20 days after refund/exchange request)',
  ],
  [SuggestionSteps.MISSING_POLICY_PAGES_shipping]: [
    'Order processing and shipping time',
    'Shipping costs (if any)',
    'International shipping process (if applicable)',
  ],
};

export const mappingPolicyPageKeyToQuestionaire = {
  [WebsitePolicyPages.SHIPPING]: [
    WebsitePolicyPagesDetailsKeys.SHIPPING_PERIOD,
    WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER,
    WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL,
  ],
  [WebsitePolicyPages.REFUND]: [
    WebsitePolicyPagesDetailsKeys.REFUND_REQUEST_PERIOD,
    WebsitePolicyPagesDetailsKeys.REFUND_PROCESS_PERIOD,
  ],
  [WebsitePolicyPages.CONTACT]: [
    WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER,
    WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL,
  ],
  [WebsitePolicyPages.PRIVACY]: [WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL],
  [WebsitePolicyPages.TERMS]: [],
};

const WebsitePolicyPagesDetailsOptions = [
  { key: 2, value: '1-2 days' },
  { key: 5, value: '3-5 days' },
  { key: 8, value: '6-8 days' },
  { key: 15, value: '9-15 days' },
  { key: 30, value: '16-30 days' },
];

const ShippingPolicyPageDetailsOptions = [
  ...WebsitePolicyPagesDetailsOptions,
  { key: null, value: 'Not Applicable' },
];

export const PolicyPageCreationQuestionaire = [
  {
    questionId: WebsitePolicyPagesDetailsKeys.SHIPPING_PERIOD,
    value: 'What is your shipping time?',
    options: ShippingPolicyPageDetailsOptions,
  },
  {
    questionId: WebsitePolicyPagesDetailsKeys.REFUND_REQUEST_PERIOD,
    value: 'What is your cancellation/refund request time?',
    options: WebsitePolicyPagesDetailsOptions,
  },
  {
    questionId: WebsitePolicyPagesDetailsKeys.REFUND_PROCESS_PERIOD,
    value: 'What is your refund processing time?',
    options: WebsitePolicyPagesDetailsOptions,
  },
];

export const defaultPolicyPageCreationFormField: PolicyPageCreationFormFieldType = {
  refund_process_period: { valid: ValidationState.NONE, value: '' },
  refund_request_period: { valid: ValidationState.NONE, value: '' },
  shipping_period: { valid: ValidationState.NONE, value: '' },
  support_contact_number: { valid: ValidationState.NONE, value: '' },
  support_email: { valid: ValidationState.NONE, value: '' },
};

export const policyPagePublishDisclaimer =
  'Pursuant to the RBI guidelines, merchants are required to clearly indicate the terms and conditions of the service and other relevant information on their website. I/we understand and acknowledge that I/we must read, modify, delete, or add any and all areas of the draft terms as necessary before using/publishing them on my/our website/payment page. I/we agree to use/publish them at my/our sole discretion and risk. I/we understand that the provision of these terms by Razorpay is not a substitute for independent legal advice, and I/we will use our independent discretion to determine the suitability of these draft terms for my/our business purposes. I/we understand and agree that Razorpay is not liable in any way or form for any liabilities that arise out of my/our free and voluntary use of these draft terms. Razorpay expressly disclaims all liability in respect of any actions taken or not taken based on any or all of the content made available. Razorpay does not endorse and is not responsible for any third-party content that may be accessed through this information.';
