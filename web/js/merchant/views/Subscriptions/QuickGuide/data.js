import { PossibleStatuses } from 'merchant/helpers/data';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  plan: (status) => {
    if (status === done) {
      return {
        title: '1. Plan Created',
        content:
          'Create your custom plans with different billing cycles and prices for your business.',
      };
    }

    return {
      title: '1. Create Plan',
      content:
        'Create your custom plans with different billing cycles and prices for your business.',
    };
  },
  subscription: (status) => {
    if (status === done) {
      return {
        title: '2. Subscription Created',
        content: 'Create subscriptions for your customers to receive recurring payments',
      };
    }

    return {
      title: '2. Create Subscription',
      content: 'Create subscriptions for your customers to receive recurring payments',
    };
  },
  payment: (status) => {
    if (status === done) {
      return {
        title: '3. Payments Received',
        content: 'Check payments received under Transactions tab.',
      };
    }

    return {
      title: '3. Receive Payments',
      content: 'Share subscription link with your customers to receive recurring payments.',
    };
  },
};
