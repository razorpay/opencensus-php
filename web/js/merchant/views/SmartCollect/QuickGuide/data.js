import { PossibleStatuses } from 'merchant/helpers/data';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  PaymentPage: (status) => {
    if (status === done) {
      return {
        title: '1. Customer Identifier created',
        content:
          'Create as many Customer Identifiers required and share the Customer Identifier details with others.',
      };
    }

    return {
      title: '1. Create Customer Identifier',
      content:
        'Create as many Customer Identifiers required and share the Customer Identifier details with others.',
    };
  },
  ReceivePayments: (status) => {
    if (status === done) {
      return {
        title: '2. Payments Received',
        content: 'You can check the payments you receive in the transactions.',
      };
    }

    return {
      title: '2. Receive Payments',
      content: 'People can pay to a Customer Identifier by adding it as a beneficiary.',
    };
  },
};
