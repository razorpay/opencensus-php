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

export { getQuery };
