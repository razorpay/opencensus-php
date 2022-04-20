import { PossibleStatuses } from 'merchant/helpers/data';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  paymentPage: (status) => {
    if (status === done) {
      return {
        title: '1. Payment Page Created',
        content:
          'Create your own custom pages by adding fields to collect relevant customer information.',
      };
    }

    return {
      title: '1. Create Payment Page',
      content:
        'Create your own custom pages by adding fields to collect relevant customer information.',
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
      content: 'Publish your page to receive payments from your customers.',
    };
  },
};
