import { PossibleStatuses } from 'merchant/helpers/data';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  Invoice: status => {
    if (status === done) {
      return {
        title: '1. Invoice Created',
        content:
          'Create GST based invoices instantly and notify your customer via sms or email.',
      };
    }

    return {
      title: '1. Create Invoice',
      content:
        'Create GST based invoices instantly and notify your customer via sms or email.',
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
        'Your customers can make payments directly via the invoice link.',
    };
  },
};
