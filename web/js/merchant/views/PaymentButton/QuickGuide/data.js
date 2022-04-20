import { PossibleStatuses } from 'merchant/helpers/data';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  createButton: (status) => {
    if (status === done) {
      return {
        title: '1. Create a Button',
        content: 'Start by creating a Payment Button to collect online payments or donations.',
      };
    }

    return {
      title: '1. Create a Button',
      content: 'Start by creating a Payment Button to collect online payments or donations.',
    };
  },
  copyAndPasteTheCode: (status) => {
    if (status === done) {
      return {
        title: '2. Copy and Paste the Code',
        content:
          'Get a single line code that you put on your website or blog to enable online payments.',
      };
    }

    return {
      title: '2. Copy and Paste the Code',
      content:
        'Get a single line code that you put on your website or blog to enable online payments.',
    };
  },
  receivePayments: (status) => {
    if (status === done) {
      return {
        title: '3. Payments Received',
        content:
          'Customers and supports will use this button to make payments on your website or blog.',
      };
    }

    return {
      title: '3. Receive Payments',
      content:
        'Customers and supports will use this button to make payments on your website or blog.',
    };
  },
};
