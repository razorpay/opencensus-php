import { PossibleStatuses } from 'rzp/utils/constants';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  PaymentPage: status => {
    if (status === done) {
      return {
        title: '1. Payment link created',
        content:
          'Create a payment link instantly and notify your customer via sms or email.',
      };
    }

    return {
      title: '1. Create Payment Link',
      content:
        'Create a payment link instantly and notify your customer via sms or email.',
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
      content: 'Your customers can make payments directly on the payment link.',
    };
  },
};
