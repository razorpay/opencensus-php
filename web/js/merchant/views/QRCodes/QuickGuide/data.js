import { PossibleStatuses } from 'merchant/helpers/data';

const { done } = PossibleStatuses;

export const getQuickGuideData = {
  QRCodes: (status) => {
    if (status === done) {
      return {
        title: '1. QR Code Created',
        content: 'Create as many QR codes as required, and print them or share with others.',
      };
    }

    return {
      title: '1. Create QR Code',
      content: 'Create as many QR codes as required, and print them or share with others.',
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
      content: 'Customers can pay by scanning your QR codes using supported apps.',
    };
  },
};
