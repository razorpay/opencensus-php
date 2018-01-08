import moment from 'moment';

import { titleCase, arrayToCsvDataUrl } from 'rzp/utils/rzp-utils';
import colors from 'rzp/utils/chart/colors.js';
import {
  globalGroupTitleMap,
  groupBy,
  groupByPlatform,
} from 'rzp/utils/pokedex.js';

const dateFormat = 'Do MMM YYYY';

const defaultGroupingVals = [
  {
    value: 'method',
    text: 'By Payment Method',
    query: ['method'],
  },
  {
    value: 'platform',
    text: 'By Platforms',
    query: ['platform', 'os', 'device'],
  },
];

function getGroupObj(value) {
  return this.grouping.filter(grouping => grouping.value === value)[0];
}

function getGroupQuery(value) {
  const groupObj = this.getGroupObj(value);

  return (groupObj && groupObj.query) || [];
}

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
    index: 'payments',
    getGroupObj,
    getGroupQuery,
    getCountQuery: function() {
      return {
        [this.name]: {
          agg_type: 'sum',
          details: {
            index: this.index,
            column: 'base_amount',
          },
        },
      };
    },
    getHistogramQuery: function(grouping, breakdown) {
      grouping = this.getGroupQuery(grouping);

      return {
        [`${this.name}Histogram`]: {
          agg_type: 'sum',
          details: {
            index: this.index,
            column: 'base_amount',
            group_by: [...grouping, `histogram_${breakdown}`],
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
    index: 'payments',
    getGroupObj,
    getGroupQuery,
    getCountQuery: function() {
      return {
        [this.name]: {
          agg_type: 'count',
          details: {
            index: this.index,
          },
        },
      };
    },
    getHistogramQuery: function(grouping, breakdown) {
      grouping = this.getGroupQuery(grouping);

      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          details: {
            index: this.index,
            group_by: [...grouping, `histogram_${breakdown}`],
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
    index: 'refunds',
    getGroupObj,
    getGroupQuery,
    getCountQuery: function() {
      return {
        [this.name]: {
          agg_type: 'count',
          details: {
            index: this.index,
            mode: 'test',
          },
        },
      };
    },
    getHistogramQuery: function(grouping, breakdown) {
      grouping = this.getGroupQuery(grouping);

      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          details: {
            index: this.index,
            mode: 'test',
            group_by: [...grouping, `histogram_${breakdown}`],
          },
        },
      };
    },
  },
  [tabsOrder[3]]: {
    name: tabsOrder[3],
    title: 'Saved Cards',
    grouping: [],
    options: [],
    index: 'payments',
    groupByColumnName: 'saved_card',
    groupTitleMap: {
      '0': 'New Card',
      '1': 'Saved Card',
    },
    getCountQuery: function() {
      return {
        [this.name]: {
          filter_key: this.name,
          agg_type: 'count',
          details: {
            index: this.index,
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
            index: this.index,
            mode: 'test',
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

export const getTimelineData = ({
  data,
  groupByColumnName,
  groupTitleMap = {},
  valueTransformer = null,
}) => {
  /*
   * Pokedex data will be completely denormalized without any grouping
   * this function, groups the data (for series) based on the
   * `groupByColumnName` parameter and transforms the data which
   * one can use directly as `data` option for chart.js
   *
   * @param {Array} data*
   * @param {String} groupByColumnName*
   * @param {Object} groupTitleMap
   * @param {Function} valueTransformer
   *
   * Description:
   * `data` is the pokedex response
   *
   * `groupByColumnName` is the key( present in each result)
   * by which the grouping should be made
   *
   * `groupTitleMap` is a dictionary that maps group values to custom names
   *
   * `valueTransformer` a function that will be called on each value in
   * the record, if passed the function will get the following arguments
   * 1) the value of the current point
   * 2) the total record given by pokedex
   * 3) the value of the group
   *
   */

  // grouping by column, ex. group by payment method (card, netbanking)
  const groupedData =
      groupByColumnName === 'platform'
        ? groupByPlatform(data)
        : groupBy(data, groupByColumnName),
    groups = Object.keys(groupedData).sort(),
    /* `timelineGroupMap` is like
         * {
         *   "<timestamp1>": {
         *     "<group1>": "<y-axis value1>",
         *     "<group2>": "<y-axis value2>",
         *     ....
         *   }
         * }
         *
         * used to get all the timestamps
         * used to check if the all groups have data for the particular
         * timestamp , else 0 will be put. it serves as a quick reference
         * of what is the value present in certain group at certain
         * timestamp
         */
    timelineGroupMap = {},
    /*
     * `datasets` contain data as defined in chart.js
     * http://www.chartjs.org/docs/latest/#creating-a-chart
     */
    datasets = [],
    /*
     * `groupDatasetsMap` contain the same info in `datasets` but indexed
     * by `groupName`
     */
    groupDatasetsMap = {},
    /*
     * `aggregates` contain the agrregate of values that we got from the
     * server to show legend
     */
    aggregates = [],
    groupAggregatesMap = {};

  // populates default data , avoids `if` conditions in next loop
  groups.forEach((groupName, index) => {
    const groupLabel =
        groupTitleMap[groupName] ||
        globalGroupTitleMap[groupName] ||
        (groupByColumnName === 'platform' ? groupName : titleCase(groupName)),
      groupColor = colors[index % colors.length];

    const dataset = {
      label: titleCase(groupLabel),
      backgroundColor: groupColor,
      borderColor: groupColor,
      data: [],
    };

    const aggregate = {
      label: groupLabel,
      color: groupColor,
      value: 0,
    };

    datasets.push(dataset);
    groupDatasetsMap[groupName] = dataset;

    aggregates.push(aggregate);
    groupAggregatesMap[groupName] = aggregate;
  });

  /*
   * calculates x-axis labels based on data from all the groups, fills
   * missing data
   */
  groups.forEach(groupName => {
    const title = groupTitleMap[groupName] || groupName,
      data = groupedData[groupName],
      otherGroups = groups.filter(item => item !== groupName);

    data.forEach(item => {
      const timestamp = item.timestamp * 1000,
        groupMap =
          timelineGroupMap[timestamp] || (timelineGroupMap[timestamp] = {});

      groupMap[groupName] =
        typeof valueTransformer === 'function'
          ? valueTransformer(item.value, item, groupName)
          : item.value;

      otherGroups.forEach(groupName => {
        groupMap[groupName] = groupMap[groupName] || 0;
      });
    });
  });

  /*
   * Transforms data into the input format for chart.js
   */
  const timestamps = Object.keys(timelineGroupMap).sort((a, b) => {
      return Number(a) - Number(b);
    }),
    groupsCsvData = [];

  let csvData = [],
    csvHeader = ['#', 'Date'],
    csvFooter = ['', 'Total'],
    grandTotal = 0;

  timestamps.forEach((timestamp, tsIndex) => {
    timestamp = Number(timestamp);

    let totalAtTime = 0;

    groups.forEach((groupName, index) => {
      const groupData = groupDatasetsMap[groupName].data,
        aggregateData = groupAggregatesMap[groupName],
        yAxisVal = timelineGroupMap[timestamp][groupName],
        groupCsvData =
          groupsCsvData[tsIndex] ||
          (groupsCsvData[tsIndex] = [moment(timestamp).format(dateFormat)]);

      // `datasets` variable will get populated due to reference
      groupData.push({
        t: timestamp,
        y: yAxisVal,
      });

      aggregateData.value += yAxisVal;

      totalAtTime += yAxisVal;

      groupCsvData.push(yAxisVal);
    });

    groupsCsvData[tsIndex].unshift(tsIndex + 1);
    groupsCsvData[tsIndex].push(totalAtTime);

    timestamps[tsIndex] = moment(timestamp);
  });

  csvData = csvData.concat(groupsCsvData);

  aggregates.forEach(aggregate => {
    csvHeader.push(aggregate.label);
    csvFooter.push(aggregate.value);

    grandTotal += aggregate.value;
  });

  csvHeader.push('Total');
  csvFooter.push(grandTotal);

  csvData.unshift(csvHeader);
  csvData.push(csvFooter);

  return {
    labels: timestamps,
    datasets,
    aggregates,
    csv: arrayToCsvDataUrl(csvData),
  };
};

export default {
  tabsMeta,
  tabsOrder,
  getQuery,
  breakdownVals,
  getTimelineData,
};
