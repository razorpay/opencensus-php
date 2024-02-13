const paymentLinksUIData = {
  default: {
    amount: '5000',
    description: 'With Minimum Partial amount, Customer Details, Receipt No and Expiry',
    accept_partial: true,
    expire_by: '1',
    additional_description: [
      {
        label: 'PL1',
        content: 'NotesValue1',
      },
    ],
    first_min_partial_amount: '20',
    reminder_enable: true,
    notify: {
      sms: '1',
      email: '1',
    },
    customer: {
      contact: '7624918474',
      email: 'qa.testing@razorpay.com',
    },
  },
  withoutPartial: {
    amount: '4000',
    description: 'Without partial payment ',
    expire_by: '1',
    additional_description: [
      {
        label: 'PL2',
        content: 'WithoutPartialPayment',
      },
    ],
    notify: {
      sms: '1',
      email: '1',
    },
    customer: {
      contact: '7624918474',
      email: 'qa.testing@razorpay.com',
    },
  },
  withMinDueAmountWithoutExpiryDate: {
    amount: '7000',
    description: 'Without expiry date ',
    accept_partial: '1',
    additional_description: [
      {
        label: 'PL3',
        content: 'WithoutExpiryDate',
      },
    ],
    first_min_partial_amount: '10',
    notify: {
      sms: '1',
      email: '1',
    },
    customer: {
      contact: '9287654321',
      email: 'anna@gmail.com',
    },
  },
  withoutMinDueAmount: {
    amount: '3000',
    description: 'Without Minimum due Amount',
    accept_partial: '1',
    additional_description: [
      {
        label: 'PL3',
        content: 'WithoutCustomerDetails',
      },
    ],
    customer: {
      contact: '9876549900',
      email: 'anna@rzp.com',
    },
  },
};

const curlecPaymentLinksUIData = {
  ...paymentLinksUIData.default,
  customer: {
    contact: '132758792',
    email: 'qa.testing@razorpay.com',
  },
};

const upiLinksData = {
  withoutExpiry: {
    amount: '4000',
    description: 'Without Reminders and Expiry Date',
    additional_description: [
      {
        label: 'PL2',
        content: 'WithoutExpiry',
      },
    ],
    reminder_enable: false,
    notify: {
      sms: '1',
      email: '1',
    },
    customer: {
      contact: '7624918474',
      email: 'qa.testing@razorpay.com',
    },
    upi_link: true,
  },
  paymentLinkWithAllParams: {
    amount: '5000',
    description: 'Customer Details, Receipt No and Expiry',
    accept_partial: '1',
    expire_by: '3',
    additional_description: [
      {
        label: 'PL1',
        content: 'NotesValue1',
      },
    ],
    reminder_enable: true,
    notify: {
      sms: '1',
      email: '1',
    },
    customer: {
      contact: '7624918474',
      email: 'qa.testing@razorpay.com',
    },
    upi_link: true,
  },
  withoutCustomerDetails: {
    amount: '7000',
    description: 'Without customer ',
    additional_description: [
      {
        label: 'PL3',
        content: 'WithoutCustomer',
      },
    ],
    upi_link: true,
    reminder_enable: true,
    expire_by: '2',
  },
};

module.exports = {
  paymentLinksUIData,
  upiLinksData,
  curlecPaymentLinksUIData,
};
