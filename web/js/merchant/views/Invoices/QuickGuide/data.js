import { PossibleStatuses } from 'merchant/helpers/data';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
const { done } = PossibleStatuses;

const INVOICE_CREATED_TEXT_MAPPING = {
  [ORG_CUSTOM_CODE_MAP.CURLEC]:
    'Create invoices instantly and notify your customer via sms or email.',
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]:
    'Create GST based invoices instantly and notify your customer via sms or email.',
};

export const getQuickGuideData = {
  invoice: (status, orgCustomCode = 'rzp') => {
    const textLabel = INVOICE_CREATED_TEXT_MAPPING[orgCustomCode];
    if (status === done) {
      return {
        title: '1. Invoice Created',
        content: textLabel,
      };
    }

    return {
      title: '1. Create Invoice',
      content: textLabel,
    };
  },
  receivePayments: (status) => {
    if (status === done) {
      return {
        title: '2. Payments Received',
        content: 'You can check the payments you receive in the transactions.',
      };
    }

    return {
      title: '2. Receive Payments',
      content: 'Your customers can make payments directly via the invoice link.',
    };
  },
};
