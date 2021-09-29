const mockStatusCalls = {};

// const emptyDowntimes = [];
const fewDropsDowntimes = [
  {
    id: 'down_HbrNFqmkK6Wmx8',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1626931299,
    end: null,
    status: 'started',
    scheduled: false,
    severity: 'low', // medium, low
    instrument: {
      network: 'Visa',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HbrNFqmkK6Wmx12',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1626931299,
    end: null,
    status: 'started',
    scheduled: false,
    severity: 'low', // medium, low
    instrument: {
      network: 'RuPay',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HbrNFqmkK6Wmx11',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1626931299,
    end: null,
    status: 'started',
    scheduled: false,
    severity: 'medium', // medium, low
    instrument: {
      network: 'American Express',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HbrNFqmkK6Wmx10',
    entity: 'payment.downtime',
    method: 'upi',
    begin: 1626931299,
    end: null,
    status: 'started',
    scheduled: false,
    severity: 'medium', // medium, low
    instrument: {
      vpa_handle: 'oksbi',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HbrNFqmkK6Wmx9',
    entity: 'payment.downtime',
    method: 'upi',
    begin: 1626931299,
    end: null,
    status: 'started',
    scheduled: false,
    severity: 'medium', // medium, low
    instrument: {
      vpa_handle: 'okicici',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HbrNFqmkK6Umx9',
    entity: 'payment.downtime',
    method: 'upi',
    begin: 1626931299,
    end: null,
    status: 'started',
    scheduled: false,
    severity: 'high', // medium, low
    instrument: {
      psp: 'amazon_pay',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HbhNFqmkK6Umx9',
    entity: 'payment.downtime',
    method: 'netbanking',
    begin: 1626931299,
    end: null,
    status: 'started',
    scheduled: false,
    severity: 'high', // medium, low
    instrument: {
      bank: 'PUNB',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HbhNFqmkK6Umx9',
    entity: 'payment.downtime',
    method: 'netbanking',
    begin: 1626931299,
    end: null,
    status: 'started',
    scheduled: false,
    severity: 'low', // medium, low
    instrument: {
      bank: 'KKBK',
    },
    created_at: 1623154421,
    updated_at: 1626931421,
  },
];

// const majorDropsDowntimes = [
//   {
//     id: 'down_HbrNFqmkK6Wmx8',
//     entity: 'payment.downtime',
//     method: 'card',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'low', // medium, low
//     instrument: {
//       network: 'Visa',
//     },
//     created_at: 1626931421,
//     updated_at: 1626931421,
//   },
//   {
//     id: 'down_HbrNFqmkK6Wmx12',
//     entity: 'payment.downtime',
//     method: 'card',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'low', // medium, low
//     instrument: {
//       network: 'RuPay',
//     },
//     created_at: 1626931421,
//     updated_at: 1626931421,
//   },
//   {
//     id: 'down_HbrNFqmkK6Wmx11',
//     entity: 'payment.downtime',
//     method: 'card',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'high', // medium, low
//     instrument: {
//       network: 'American Express',
//     },
//     created_at: 1626931421,
//     updated_at: 1626931421,
//   },
//   {
//     id: 'down_HbrNFqmkK6Wmx10',
//     entity: 'payment.downtime',
//     method: 'upi',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'high', // medium, low
//     instrument: {
//       vpa_handle: 'oksbi',
//     },
//     created_at: 1626931421,
//     updated_at: 1626931421,
//   },
//   {
//     id: 'down_HbrNFqmkK6Wmx9',
//     entity: 'payment.downtime',
//     method: 'upi',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'high', // medium, low
//     instrument: {
//       vpa_handle: 'okicici',
//     },
//     created_at: 1626931421,
//     updated_at: 1626931421,
//   },
//   {
//     id: 'down_HbrNFqmkK6Wmx9',
//     entity: 'payment.downtime',
//     method: 'upi',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'high', // medium, low
//     instrument: {
//       vpa_handle: 'okhdfc',
//     },
//     created_at: 1626931421,
//     updated_at: 1626931421,
//   },
//   {
//     id: 'down_HbrNFqmkK6Umx9',
//     entity: 'payment.downtime',
//     method: 'upi',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'high', // medium, low
//     instrument: {
//       psp: 'amazon_pay',
//     },
//     created_at: 1626931421,
//     updated_at: 1626931421,
//   },
//   {
//     id: 'down_HbhNFqmkK6Umx9',
//     entity: 'payment.downtime',
//     method: 'netbanking',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'high', // medium, low
//     instrument: {
//       bank: 'PUNB',
//     },
//     created_at: 1626931421,
//     updated_at: 1626931421,
//   },
//   {
//     id: 'down_HbhNFqmkK6Umx9',
//     entity: 'payment.downtime',
//     method: 'netbanking',
//     begin: 1626931299,
//     end: null,
//     status: 'started',
//     scheduled: false,
//     severity: 'low', // medium, low
//     instrument: {
//       bank: 'UTIB',
//     },
//     created_at: 1623154421,
//     updated_at: 1626931421,
//   },
// ];

const scheduledDowntimes = [
  {
    id: 'down_HbrNFqmkK6Wmx8',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1626931299,
    end: 1626931299,
    status: 'started',
    scheduled: true,
    severity: 'high', // medium, low
    instrument: {
      network: 'Visa',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HhrNFqmkK6Wmx8',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1626931299,
    end: 1626931299,
    status: 'started',
    scheduled: true,
    severity: 'low', // medium, low
    instrument: {
      issuer: 'CNRB',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HhrNFxmkK6Wmx8',
    entity: 'payment.downtime',
    method: 'upi',
    begin: 1626931245,
    end: 1626931299,
    status: 'started',
    scheduled: true,
    severity: 'medium', // medium, low
    instrument: {
      psp: 'phonepe',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HhrNFxmkK6Wmx8',
    entity: 'payment.downtime',
    method: 'upi',
    begin: 1626931245,
    end: 1626931299,
    status: 'started',
    scheduled: true,
    severity: 'medium', // medium, low
    instrument: {
      vpa_handle: 'okaxis',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
  {
    id: 'down_HhrNFxmkK6Wmx8',
    entity: 'payment.downtime',
    method: 'netbanking',
    begin: 1626931245,
    end: 1626931299,
    status: 'started',
    scheduled: true,
    severity: 'medium', // medium, low
    instrument: {
      bank: 'KKBK',
    },
    created_at: 1626931421,
    updated_at: 1626931421,
  },
];

const historicalDowntimes = [
  {
    id: 'down_HvT1R0X3K52ame',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1631212337,
    end: 1631212960,
    status: 'resolved',
    scheduled: false,
    severity: 'high',
    instrument: {
      issuer: 'KKBK',
    },
    created_at: 1631212465,
    updated_at: 1631212960,
  },
  {
    id: 'down_Hv1XXDqG3boTA0',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1631115538,
    end: 1631116806,
    status: 'resolved',
    scheduled: false,
    severity: 'high',
    instrument: {
      issuer: 'BKID',
    },
    created_at: 1631115683,
    updated_at: 1631116806,
  },
  {
    id: 'down_Hv1XXDoxlGP3B8',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1631115538,
    end: 1631116804,
    status: 'resolved',
    scheduled: false,
    severity: 'high',
    instrument: {
      issuer: 'BKID',
    },
    created_at: 1631115683,
    updated_at: 1631116804,
  },
  {
    id: 'down_Hv09H4QJQHzpLA',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1631110639,
    end: 1631113347,
    status: 'resolved',
    scheduled: false,
    severity: 'high',
    instrument: {
      issuer: 'SBIN',
    },
    created_at: 1631110784,
    updated_at: 1631113347,
  },
  {
    id: 'down_HuzmtFgKXOA99M',
    entity: 'payment.downtime',
    method: 'card',
    begin: 1631109362,
    end: 1631115469,
    status: 'resolved',
    scheduled: false,
    severity: 'high',
    instrument: {
      issuer: 'BKID',
    },
    created_at: 1631109512,
    updated_at: 1631115469,
  },
];

mockStatusCalls.getOngoingDowntimes = () => {
  return new Promise((res) => {
    res({
      data: fewDropsDowntimes,
    });
  });
};

mockStatusCalls.getScheduledDowntimes = () => {
  return new Promise((res) => {
    res({ data: scheduledDowntimes });
  });
};

mockStatusCalls.getHistoricalDowntimes = () => {
  return new Promise((res) => {
    res({ data: historicalDowntimes });
  });
};

export default mockStatusCalls;
