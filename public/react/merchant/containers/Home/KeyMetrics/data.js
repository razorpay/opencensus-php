import moment from 'moment';

import {
  titleCase,
  arrayToCsvDataUrl,
  paiseToRupees
} from 'rzp/utils/rzp-utils';
import colors from 'rzp/utils/chart/colors.js';
import {
  globalGroupTitleMap,
  groupBy,
  groupByPlatform,
  getDefaultFilter,
  getDefaultPaymentFilter
} from 'rzp/utils/pokedex';

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

export const breakdownVals = ['daily', 'weekly', 'monthly'];

const TRANSACTION_VOLUME = "transactionVolume",
      NUM_TRANSACTIONS = "numTransactions",
      REFUNDS = "refunds",
      SAVED_CARDS = "savedCards";

export {
  TRANSACTION_VOLUME,
  NUM_TRANSACTIONS,
  SAVED_CARDS,
  REFUNDS,
};

export const tabsOrder = [
  TRANSACTION_VOLUME,
  NUM_TRANSACTIONS,
  SAVED_CARDS,
  REFUNDS,
];

export const tabsMeta = {
  [TRANSACTION_VOLUME]: {
    name: TRANSACTION_VOLUME,
    title: 'Payment Volume',
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
  [NUM_TRANSACTIONS]: {
    name: NUM_TRANSACTIONS,
    title: 'Number of Payments',
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
  [REFUNDS]: {
    name: REFUNDS,
    title: 'Number of Refunds',
    grouping: defaultGroupingVals,
    options: [],
    index: 'refunds',
    getGroupObj,
    getGroupQuery,
    getCountQuery: function() {
      return {
        [this.name]: {
          agg_type: 'count',
          filter_key: 'refunds',
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
          filter_key: 'refunds',
          details: {
            index: this.index,
            group_by: [...grouping, `histogram_${breakdown}`],
          },
        },
      };
    },
    getFilterQuery: function (startTime, endTime) {
    
      return {
        "refunds" : [
          getDefaultFilter(startTime, endTime)
        ]
      }
    }
  },
  [SAVED_CARDS]: {
    name: SAVED_CARDS,
    title: 'Saved Card Payments',
    grouping: [],
    options: [],
    index: 'payments',
    groupByColumnName: 'saved_card',
    groupTitleMap: {
      '0': 'All Card Payments',
      '1': 'Saved Card Payments',
    },
    getCountQuery: function() {
      return {
        [this.name]: {
          filter_key: this.name,
          agg_type: 'count',
          details: {
            index: this.index,
          },
        },
      };
    },
    getHistogramQuery: function(grouping, breakdown) {
      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          filter_key: 'cardsOnly',
          details: {
            index: this.index,
            group_by: ['saved_card', `histogram_${breakdown}`],
          }
        },
      };
    },
    getFilterQuery: function(startTime, endTime) {
      const defaultFilter = getDefaultPaymentFilter(
                              startTime,
                              endTime
                            );

      return {
        [this.name]: [
          {
            ...defaultFilter,
            saved_card: true,
          },
        ],
        "cardsOnly": [
          {
            ...defaultFilter,
            method: ["card", "emi"]
          }
        ],
      };
    },
  },
};

export const getQuery = options => {
  const {
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
        ? tabMeta.getFilterQuery(startTime, endTime)
        : {"default": [
             getDefaultPaymentFilter(startTime, endTime)
          ]},
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

const momentDurationFuncMap = {
    daily: 'asDays',
    weekly: 'asWeeks',
    monthly: 'asMonths',
  },
  momentDurationMap = {
    daily: 'days',
    weekly: 'weeks',
    monthly: 'months',
  };

export const getTimelineData = ({
  data,
  groupByColumnName,
  startTime,
  endTime,
  breakdown = 'daily',
  groupTitleMap = {},
  isCurrency = false
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
   *
   * Description:
   * `data` is the pokedex response
   *
   * `groupByColumnName` is the key( present in each result)
   * by which the grouping should be made
   *
   * `groupTitleMap` is a dictionary that maps group values to custom names
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

  let csvData = [],
    csvHeader = ['#', 'Date'],
    csvFooter = ['', 'Total'],
    csvGrandTotal = 0;

  if (groups.length === 0) {

    csvData = csvData.concat([csvHeader, csvFooter.concat([0])]);

    return {
      labels: [],
      datasets: [],
      aggregates: [],
      csv: arrayToCsvDataUrl(csvData),
    };
  }

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

  groups.forEach(groupName => {
    const title = groupTitleMap[groupName] || groupName,
      data = groupedData[groupName],
      otherGroups = groups.filter(item => item !== groupName);

    data.forEach(item => {
      const timestamp = item.timestamp * 1000,
        groupMap =
          timelineGroupMap[timestamp] || (timelineGroupMap[timestamp] = {});

      groupMap[groupName] = item.value;

      otherGroups.forEach(groupName => {
        groupMap[groupName] = groupMap[groupName] || 0;
      });
    });
  });

  /*
   * Transforms data into the input format for chart.js
   */
  let timestamps = Object.keys(timelineGroupMap).sort((a, b) => {
      return Number(a) - Number(b);
    }),
    groupsCsvData = [],
    startMs = moment(startTime * 1000),
    endMs = moment(endTime * 1000),
    firstMs = Number(timestamps[0]),
    lastMs = Number(timestamps[timestamps.length - 1]);

  if (breakdown === 'daily') {
    const firstDayStart = moment(firstMs)
        .startOf('day')
        .toDate(),
      lastDayStart = moment(lastMs)
        .startOf('day')
        .toDate();

    startMs = moment(startMs)
      .startOf('day')
      .toDate();
    endMs = moment(endMs)
      .startOf('day')
      .toDate();

    if (firstDayStart > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastDayStart < endMs) {
      timestamps.push(endMs.getTime());
    }
  } else if (breakdown === 'weekly') {
    // momentjs start of week is sunday, whereas
    // pokedex start of week is monday, so adding 1 day
    const firstWeekStart = moment(firstMs)
        .startOf('week')
        .add(1, 'days')
        .toDate(),
      lastWeekStart = moment(lastMs)
        .startOf('week')
        .add(1, 'days')
        .toDate();

    startMs = moment(startMs)
      .startOf('week')
      .add(1, 'days')
      .toDate();
    endMs = moment(endMs)
      .startOf('week')
      .add(1, 'days')
      .toDate();

    if (firstWeekStart > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastWeekStart < endMs) {
      timestamps.push(endMs.getTime());
    }
  } else if (breakdown === 'monthly') {
    const firstMonthStart = moment(firstMs)
        .startOf('month')
        .toDate()
        .getTime(),
      lastMonthStart = moment(lastMs)
        .startOf('month')
        .toDate()
        .getTime();

    startMs = moment(startMs)
      .startOf('month')
      .toDate();
    endMs = moment(endMs)
      .startOf('month')
      .toDate();

    if (firstMonthStart > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastMonthStart < endMs) {
      timestamps.push(endMs.getTime());
    }
  }

  timestamps = timestamps.reduce((result, timestamp) => {
    /* fill in the missing data points*/

    timestamp = Number(timestamp);
    let prevTimestamp = result[result.length - 1];

    if (!prevTimestamp) {
      result.push(timestamp);
      return result;
    }

    let numPointsGap = moment
      .duration(timestamp - prevTimestamp)
      [momentDurationFuncMap[breakdown]]();

    while (numPointsGap > 1) {
      prevTimestamp = moment(prevTimestamp)
        .add(1, momentDurationMap[breakdown])
        .toDate()
        .getTime();

      result.push(prevTimestamp);

      numPointsGap--;
    }

    result.push(timestamp);

    return result;
  }, []);

  timestamps.forEach((timestamp, tsIndex) => {
    // if missing value
    if (!timelineGroupMap[timestamp]) {
      const groupMap = (timelineGroupMap[timestamp] = {});

      groups.forEach(groupName => {
        groupMap[groupName] = 0;
      });
    }

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
        y: isCurrency ? paiseToRupees(yAxisVal) : yAxisVal,
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

    csvGrandTotal += aggregate.value;
    aggregate.value = isCurrency
                        ? paiseToRupees(aggregate.value)
                        : aggregate.value;
  });

  csvHeader.push(`Total${isCurrency ? "(Paise)" : ""}`);
  csvFooter.push(csvGrandTotal);

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
