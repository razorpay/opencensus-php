export const payments = {
  paymentId: {
    authorized: {
      netbanking: 'pay_MWkEWbfbz46Wsk',
      emi: 'pay_MWgFTBM47GDA2T',
      upi: 'pay_MWm0faxRp7Whwh',
    },
    captured: {
      wallet: 'pay_MWb0KQ9xSJPNvk',
      netbanking: 'pay_MWar3gNs2Tzs18',
      upi: 'pay_MWkwOgjXPdZBly',
    },
    created: {
      netbanking: 'pay_MWaxTM6QW05iz9',
    },
    failed: {
      netbanking: 'pay_MWawypCQ6dWyhD',
    },
    refunded: {
      netbanking: 'pay_MWavaGTL2MpX8U',
    },
  },
};

export const refunds = {
  refundId: {
    fullRefund: {
      processed: 'rfnd_MWayrWkO6fj30Y',
    },
    partialRefund: {
      processed: 'rfnd_MWnRj88R2XCcCn',
      multiPartialRefundProcessed: 'rfnd_MWjtqmCqdWQza7',
    },
  },
};

export const uploadInvoices = {
  paymentId: {
    allDetails: 'pay_NMU5tjbkt8Vxys',
    noSenderName: 'pay_NMTpcCTxFlzjg2',
    noSenderCountry: 'pay_NMOIqK82Wvu7I7',
    noSenderAddr: 'pay_NMOMXwQAgFtuK2',
  },
};
