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

const mappingPolicyPageKeyToQuestionaire = {
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

export const getQuestionaireDetailsFromPolicyPagesToBeGenerated = (
  policyPagesToGenerate: Array<Partial<WebsitePolicyPages>>,
) => {
  const questions: Array<Partial<WebsitePolicyPagesDetailsKeys>> = [];

  policyPagesToGenerate.forEach((policyPage) => {
    const mappedQuestions = mappingPolicyPageKeyToQuestionaire[policyPage];

    if (mappedQuestions) {
      mappedQuestions.forEach((question) => {
        if (!questions.includes(question)) {
          questions.push(question);
        }
      });
    }
  });
  const keys = Object.values(WebsitePolicyPagesDetailsKeys);

  const result: Record<WebsitePolicyPagesDetailsKeys, boolean> = {} as Record<
    WebsitePolicyPagesDetailsKeys,
    boolean
  >;
  let isEmpty = true;
  keys.forEach((key) => {
    const isPresent = questions.includes(key as WebsitePolicyPagesDetailsKeys);
    if (isPresent) {
      isEmpty = false;
    }
    result[key] = isPresent;
  });

  return { isEmpty, questionaireMapping: result };
};

const WebsitePolicyPagesDetailsOptions = [
  { key: 2, value: '1-2 days' },
  { key: 5, value: '3-5 days' },
  { key: 8, value: '6-8 days' },
  { key: 15, value: '9-15 days' },
  { key: 30, value: '16-30 days' },
  { key: null, value: 'Not Applicable' },
];

export const PolicyPageCreationQuestionaire = [
  {
    questionId: WebsitePolicyPagesDetailsKeys.SHIPPING_PERIOD,
    value: 'Shipping time',
    options: WebsitePolicyPagesDetailsOptions,
  },
  {
    questionId: WebsitePolicyPagesDetailsKeys.REFUND_REQUEST_PERIOD,
    value: 'Cancellation / Refund request time',
    options: WebsitePolicyPagesDetailsOptions,
  },
  {
    questionId: WebsitePolicyPagesDetailsKeys.REFUND_PROCESS_PERIOD,
    value: 'Refund processing time',
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
