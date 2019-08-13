import { PossibleStatuses } from 'rzp/utils/constants';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  LinkedAccount: status => {
    if (status === done) {
      return {
        title: '1. Linked Account Created',
        content:
          'Easily add your vendor/seller/service provider account details as a linked account.',
      };
    }

    return {
      title: '1. Create a Linked Account',
      content:
        'Easily add your vendor/seller/service provider account details as a linked account.',
    };
  },
  Transfers: status => {
    if (status === done) {
      return {
        title: '2. Transfer Initiated',
        content:
          'Initiate the payment to be transferred to a linked account from your transactions. Show me how',
      };
    }

    return {
      title: '2. Initiate transfers',
      content:
        'Initiate the payment to be transferred to a linked account from your transactions. Show me how',
    };
  },
};
