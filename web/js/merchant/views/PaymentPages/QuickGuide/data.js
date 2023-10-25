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

export const getBatchQuickGuideData = {
  paymentPage: {
    title: '1. Create Payment Page',
    content: 'Create your own custom page by adding data fields as required.',
  },
  uploadFile: {
    title: '2. Upload File Based Data',
    content: 'As per the fields created on the page, upload data via file upload and publish page.',
  },
  receivePayments: {
    title: '3. Receive Payments',
    content:
      'Customers can enter pre defined input on the page and view details uploaded for them basis which they can make the payment using the mode of their choice.',
  },
};
