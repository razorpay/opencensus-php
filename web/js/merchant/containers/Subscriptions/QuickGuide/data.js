import { PossibleStatuses } from 'rzp/utils/constants';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  Plan: status => {
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
  Subscription: status => {
    if (status === done) {
      return {
        title: '2. Subscription Created',
        content:
          'Create subscriptions of the plans to receive recurring payments from your customers.',
      };
    }

    return {
      title: '2. Create Subscription',
      content:
        'Create subscriptions of the plans to receive recurring payments from your customers.',
    };
  },
  Payment: status => {
    if (status === done) {
      return {
        title: '3. Payments Received',
        content: 'You can check the payments you receive in the transactions.',
      };
    }

    return {
      title: '3. Receive Payments',
      content:
        'Share the link of a subscription with your customers to receive payments.',
    };
  },
};
