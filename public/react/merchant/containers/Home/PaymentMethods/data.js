const getQuery = ({ merchantId, startTime, endTime }) => ({
  filters: {
    default: [
      {
        merchant_id: [merchantId],
        created_at: {
          gte: startTime,
          lte: endTime,
        },
      },
    ],
  },
  aggregations: {
    agg: {
      agg_type: 'percent',
      details: {
        index: 'payments',
        column: 'base_amount',
        group_by: [
          'merchant_id',
          'method',
          'bank',
          'issuer',
          'network',
          'wallet',
          'type',
        ],
      },
    },
  },
});

export { getQuery };
