const oldestTransactionQuery = {
  filters: {
    default: [
      {
        merchant_id: ['10000000000000'],
      },
    ],
  },
  aggregations: {
    records: {
      agg_type: 'oldest',
      details: {
        index: 'payments',
        limit: 1,
        result_fields: ['created_at'],
      },
    },
  },
};

export { oldestTransactionQuery };
