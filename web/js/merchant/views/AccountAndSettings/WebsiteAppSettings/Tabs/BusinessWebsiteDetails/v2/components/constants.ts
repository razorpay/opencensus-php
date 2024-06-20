import { SuggestionSteps } from '../types';

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
