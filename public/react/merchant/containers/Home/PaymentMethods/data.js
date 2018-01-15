const getQuery = ({ merchantId, startTime, endTime }) => ({
  filters: {
    default: [
      {
        created_at: {
          gte: startTime,
          lte: endTime,
        },
      },
    ],
  },
  aggregations: {
    agg: {
      agg_type: 'sum',
      details: {
        index: 'payments',
        column: 'base_amount',
        group_by: ['method', 'bank', 'issuer', 'network', 'wallet', 'type'],
      },
    },
  },
});

const sampleData = {
  success: true,
  data: {
    agg: {
      result: [
        {
          network: 'Visa',
          value: 52400,
          wallet: '',
          type: 'credit',
          method: 'card',
          bank: '',
          issuer: 'YESB',
        },
        {
          network: '',
          value: 5000,
          wallet: '',
          type: '',
          method: 'netbanking',
          bank: 'ICIC',
          issuer: '',
        },
        {
          network: '',
          value: 400,
          wallet: 'payumoney',
          type: '',
          method: 'wallet',
          bank: '',
          issuer: '',
        },
        {
          network: '',
          value: 100000,
          wallet: '',
          type: '',
          method: 'netbanking',
          bank: 'CORP',
          issuer: '',
        },
        {
          network: '',
          value: 20034,
          wallet: '',
          type: '',
          method: 'netbanking',
          bank: 'YESB',
          issuer: '',
        },
        {
          network: '',
          value: 10000,
          wallet: '',
          type: '',
          method: 'netbanking',
          bank: 'SBIN',
          issuer: '',
        },
      ],
      last_updated_at: 1516022103,
    },
  },
};

export { getQuery, sampleData };
