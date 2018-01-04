export const groupValues = ['transactionVolume', 'noTransactions'];

export const groupMeta = {
  [groupValues[0]]: {
    title: 'By Transaction Volume',
    aggType: 'sum',
    column: 'base_amount',
    groupBy: 'platform',
    isCurrency: true,
  },
  [groupValues[1]]: {
    title: 'By No. of Transactions',
    aggType: 'count',
    groupBy: 'platform',
  },
};

export const getQuery = ({ merchantId, startTime, endTime, group }) => {
  const meta = groupMeta[group];

  return {
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
      distribution: {
        agg_type: meta.aggType,
        details: {
          index: 'payments',
          group_by: ['platform', 'os', 'device'],
          ...(!!meta.column && { column: meta.column }),
        },
      },
    },
  };
};
