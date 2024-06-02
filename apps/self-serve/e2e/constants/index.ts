export const BASE_PATH = '../../playwright/.auth';

export const payments = {
  paymentId: {
    authorized: {
      netbanking: 'pay_MWkEWbfbz46Wsk',
      emi: 'pay_MWgFTBM47GDA2T',
      upi: 'pay_MWm0faxRp7Whwh',
      intlbanktransfer: 'pay_Ncisu2YovXxYxn',
    },
    captured: {
      wallet: 'pay_MWb0KQ9xSJPNvk',
      netbanking: 'pay_MWar3gNs2Tzs18',
      upi: 'pay_MWkwOgjXPdZBly',
      card: 'pay_NaOk9ruBJogGYA',
    },
    created: {
      netbanking: 'pay_NgmvbcWMb0fGy3',
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
      processed: 'rfnd_NgpqHUeMHyLW41',
      multiPartialRefundProcessed: 'rfnd_MWjtqmCqdWQza7',
    },
  },
};

export const uploadInvoices = {
  paymentId: {
    allDetails: 'pay_NMO3gH6Jx2JF2y',
    noSenderName: 'pay_NMTpcCTxFlzjg2',
    noSenderCountry: 'pay_NMOIqK82Wvu7I7',
    noSenderAddr: 'pay_NMOMXwQAgFtuK2',
  },
};
