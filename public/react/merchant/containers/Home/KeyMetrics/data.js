const defaultGroupingVals = [
  { value: 'method', text: 'By Payment Method' },
  { value: 'platform', text: 'By Platforms' },
];

const getDefaultFilterQuery = (merchantId, startTime, endTime) => {
  return {
    default: [
      {
        merchant_id: [merchantId],
        created_at: {
          gte: startTime,
          lte: endTime,
        },
      },
    ],
  };
};

export const breakdownVals = ['daily', 'weekly', 'monthly'];

export const tabsOrder = [
  'transactionVolume',
  'numTransactions',
  'refunds',
  'savedCards',
];

export const tabsMeta = {
  [tabsOrder[0]]: {
    name: tabsOrder[0],
    title: 'Transaction Volume',
    grouping: defaultGroupingVals,
    options: [],
    isCurrency: true,
    getCountQuery: function() {
      return {
        [this.name]: {
          agg_type: 'sum',
          details: {
            index: 'payments',
            column: 'base_amount',
          },
        },
      };
    },
    getHistogramQuery: function(grouping, breakdown) {
      return {
        [`${this.name}Histogram`]: {
          agg_type: 'sum',
          details: {
            index: 'payments',
            column: 'base_amount',
            group_by: [grouping, `histogram_${breakdown}`],
          },
        },
      };
    },
  },
  [tabsOrder[1]]: {
    name: tabsOrder[1],
    title: 'Number of Transactions',
    grouping: defaultGroupingVals,
    options: [],
    getCountQuery: function() {
      return {
        [this.name]: {
          agg_type: 'count',
          details: {
            index: 'payments',
          },
        },
      };
    },
    getHistogramQuery: function(grouping, breakdown) {
      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          details: {
            index: 'payments',
            group_by: [grouping, `histogram_${breakdown}`],
          },
        },
      };
    },
  },
  [tabsOrder[2]]: {
    name: tabsOrder[2],
    title: 'Refunds in total',
    grouping: defaultGroupingVals,
    options: [],
    getCountQuery: function() {
      return {
        [this.name]: {
          agg_type: 'count',
          details: {
            index: 'refunds',
            mode: 'test',
          },
        },
      };
    },
    getHistogramQuery: function(grouping, breakdown) {
      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          details: {
            index: 'refunds',
            mode: 'test',
            group_by: [grouping, `histogram_${breakdown}`],
          },
        },
      };
    },
  },
  [tabsOrder[3]]: {
    name: tabsOrder[3],
    title: 'New Saved Cards',
    grouping: [],
    options: [],
    getCountQuery: function() {
      return {
        [this.name]: {
          filter_key: this.name,
          agg_type: 'count',
          details: {
            index: 'payments',
          },
        },
      };
    },
    getHistogramQuery: function(grouping, breakdown) {
      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          filter_key: this.name,
          details: {
            index: 'payments',
            group_by: ['saved_card', `histogram_${breakdown}`],
          },
        },
      };
    },
    getFilterQuery: function(merchantId, startTime, endTime) {
      return {
        [this.name]: [
          {
            merchant_id: [merchantId],
            created_at: {
              gte: startTime,
              lte: endTime,
            },
            saved_card: true,
          },
        ],
      };
    },
  },
};

export const getQuery = options => {
  const {
      merchantId,
      startTime,
      endTime,
      tabName,
      groupBy,
      breakdown,
      countsOnly,
      fetchHistogramForTab,
    } = options,
    query = {};

  if (tabsMeta[tabName]) {
    const tabMeta = tabsMeta[tabName];

    return {
      filters: tabMeta.getFilterQuery
        ? tabMeta.getFilterQuery(merchantId, startTime, endTime)
        : getDefaultFilterQuery(merchantId, startTime, endTime),
      aggregations: {
        ...tabMeta.getCountQuery(),
        ...(!countsOnly && tabMeta.getHistogramQuery(groupBy, breakdown)),
      },
    };
  }

  return tabsOrder.reduce(
    (result, tabName) => {
      const query = getQuery({
        ...options,
        tabName,
        countsOnly: tabName !== fetchHistogramForTab,
      });

      result.filters = { ...result.filters, ...query.filters };
      result.aggregations = { ...result.aggregations, ...query.aggregations };

      return result;
    },
    { filters: {}, aggregations: {} }
  );
};

export default {
  tabsMeta,
  tabsOrder,
  getQuery,
  breakdownVals,
};
