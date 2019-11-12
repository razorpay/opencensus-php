import { PossibleStatuses } from 'common/utils/constants';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  PaymentPage: status => {
    if (status === done) {
      return {
        title: '1. Virtual account created',
        content:
          'Create as many virtual accounts required and share the account details with others.',
      };
    }

    return {
      title: '1. Create Virtual account',
      content:
        'Create as many virtual accounts required and share the account details with others.',
    };
  },
  ReceivePayments: status => {
    if (status === done) {
      return {
        title: '2. Payments Received',
        content: 'You can check the payments you receive in the transactions.',
      };
    }

    return {
      title: '2. Receive Payments',
      content:
        'People can pay to a virtual account by adding it as a beneficiary.',
    };
  },
};
