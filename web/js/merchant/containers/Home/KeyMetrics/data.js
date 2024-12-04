import moment from 'moment';

import {
  titleCase,
  isDefined,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  arrayToCsvDataUrl,
  groupBy,
} from 'common/utils/rzp-utils';
// eslint-disable-next-line import/extensions
import colors from 'common/utils/chart/colors.js';
import {
  globalGroupTitleMap,
  groupByPlatform,
  getDefaultFilter,
  getDefaultPaymentFilter,
  platformGroupingVals,
} from 'common/utils/pokedex';

const dateFormat = 'Do MMM YYYY';

const TRANSACTION_VOLUME = 'transactionVolume';
const NUM_TRANSACTIONS = 'numTransactions';
const REFUNDS = 'refunds';
const SAVED_CARDS = 'savedCards';
const PLATFORM = 'platform';
const CUMULATIVE = 'Total';
const METHOD = 'method';
const SAVED_CARD_PAYMENTS = 'Saved Card Payments';

const TABS_FOR_JK_ORG = [NUM_TRANSACTIONS, TRANSACTION_VOLUME];

export {
  TRANSACTION_VOLUME,
  NUM_TRANSACTIONS,
  SAVED_CARDS,
  REFUNDS,
  PLATFORM,
  CUMULATIVE,
  METHOD,
  SAVED_CARD_PAYMENTS,
  TABS_FOR_JK_ORG,
};

const defaultGroupingVals = [
  {
    value: CUMULATIVE,
    text: 'By Total Volume',
    query: [],
  },
  {
    value: 'method',
    text: 'By Payment Method',
    query: ['method'],
  },
  {
    value: PLATFORM,
    text: 'By Platforms',
    query: platformGroupingVals,
  },
];

function getGroupObj(value) {
  // eslint-disable-next-line babel/no-invalid-this
  return this.grouping.filter((grouping) => grouping.value === value)[0];
}

function getGroupQuery(value) {
  // eslint-disable-next-line babel/no-invalid-this
  const groupObj = this.getGroupObj(value);

  return (groupObj && groupObj.query) || [];
}

export const breakdownValsMap = {
  hourly: {
    value: 'hourly',
    isEnabled: (startDate, endDate) => endDate.diff(startDate, 'days') <= 3,
    title: 'Hourly',
    disabledText: 'Available for a date range within 3 days',
  },
  daily: {
    value: 'daily',
    isEnabled: (startDate, endDate) => !startDate.isSame(endDate, 'day'),
    title: 'Daily',
  },
  weekly: {
    value: 'weekly',
    isEnabled: (startDate, endDate) => !startDate.isSame(endDate, 'isoWeek'),
    title: 'Weekly',
    disabledText: 'Available for a date range across multiple weeks',
  },
  monthly: {
    value: 'monthly',
    isEnabled: (startDate, endDate) => !startDate.isSame(endDate, 'month'),
    title: 'Monthly',
    disabledText: 'Available for a date range across multiple months',
  },
};

export const breakdownVals = [
  breakdownValsMap.hourly,
  breakdownValsMap.daily,
  breakdownValsMap.weekly,
  breakdownValsMap.monthly,
];

export const tabsOrder = [TRANSACTION_VOLUME, NUM_TRANSACTIONS, SAVED_CARDS, REFUNDS];

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
    helpText:
      'Payment volume is the amount of "authorised"' +
      'payments, which were created in the selected time range.',
    // eslint-disable-next-line func-names
    getCountQuery: function () {
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
    // eslint-disable-next-line func-names
    getHistogramQuery: function ({ groupBy, breakdown }) {
      groupBy = this.getGroupQuery(groupBy);

      return {
        [`${this.name}Histogram`]: {
          agg_type: 'sum',
          details: {
            index: this.index,
            column: 'base_amount',
            group_by: [...groupBy, `histogram_${breakdown}`],
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
    helpText:
      // eslint-disable-next-line no-useless-concat
      'Number of "authorised" payments, which' + ' were created in the selected time range.',
    // eslint-disable-next-line func-names
    getCountQuery: function () {
      return {
        [this.name]: {
          agg_type: 'count',
          details: {
            index: this.index,
          },
        },
      };
    },
    // eslint-disable-next-line func-names
    getHistogramQuery: function ({ groupBy, breakdown }) {
      groupBy = this.getGroupQuery(groupBy);

      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          details: {
            index: this.index,
            group_by: [...groupBy, `histogram_${breakdown}`],
          },
        },
      };
    },
  },
  [REFUNDS]: {
    name: REFUNDS,
    title: 'Number of Refunds',
    grouping: [...defaultGroupingVals.slice(0, 2)],
    options: [],
    index: 'refunds',
    getGroupObj,
    getGroupQuery,
    helpText: 'Number of refunds created in the selected time range.',
    // eslint-disable-next-line func-names
    getCountQuery: function () {
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
    // eslint-disable-next-line func-names
    getHistogramQuery: function ({ groupBy, breakdown }) {
      groupBy = this.getGroupQuery(groupBy);

      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          filter_key: 'refunds',
          details: {
            index: this.index,
            group_by: [...groupBy, `histogram_${breakdown}`],
          },
        },
      };
    },
    // eslint-disable-next-line func-names
    getFilterQuery: function (startTime, endTime) {
      return {
        refunds: [getDefaultFilter(startTime, endTime)],
      };
    },
  },
  [SAVED_CARDS]: {
    name: SAVED_CARDS,
    title: SAVED_CARD_PAYMENTS,
    grouping: [],
    options: [],
    index: 'payments',
    groupByColumnName: 'saved_card',
    isPercent: true,
    helpText: 'Percentage of number of saved card payments, compared to all the card payments.',
    groupTitleMap: {
      0: 'Other Card Payments',
      1: SAVED_CARD_PAYMENTS,
    },
    // eslint-disable-next-line func-names
    getCountQuery: function () {
      return {
        [this.name]: {
          filter_key: this.name,
          agg_type: 'count',
          details: {
            index: this.index,
            group_by: [this.groupByColumnName],
          },
        },
      };
    },
    // eslint-disable-next-line func-names
    getHistogramQuery: function ({ breakdown }) {
      return {
        [`${this.name}Histogram`]: {
          agg_type: 'count',
          filter_key: this.name,
          details: {
            index: this.index,
            group_by: [this.groupByColumnName, `histogram_${breakdown}`],
          },
        },
      };
    },
    // eslint-disable-next-line func-names
    getFilterQuery: function (startTime, endTime) {
      const defaultFilter = getDefaultPaymentFilter(startTime, endTime);

      return {
        [this.name]: [
          {
            ...defaultFilter,
            method: ['card', 'emi'],
          },
        ],
      };
    },
  },
};

export const getQuery = (options) => {
  const {
    startTime,
    endTime,
    tabName,
    groupBy,
    filterBy,
    breakdown,
    countsOnly,
    includeHistogramForTab,
  } = options;

  // given tab name, returns query for only that tab
  if (tabsMeta[tabName]) {
    const tabMeta = tabsMeta[tabName];

    return {
      filters: tabMeta.getFilterQuery
        ? tabMeta.getFilterQuery(startTime, endTime, filterBy)
        : {
            default: [getDefaultPaymentFilter(startTime, endTime)],
          },
      aggregations: {
        ...tabMeta.getCountQuery(filterBy),
        ...(!countsOnly &&
          tabMeta.getHistogramQuery({
            groupBy,
            breakdown,
            filterBy,
          })),
      },
    };
  }

  // if tabName is not given, get query for all tabs,
  // by default only count queries are returned, if query for
  // histogram is needed , give the tab name in `tabName`
  return tabsOrder.reduce(
    (result, tabName) => {
      const query = getQuery({
        ...options,
        tabName,
        countsOnly: tabName !== includeHistogramForTab,
      });

      result.filters = { ...result.filters, ...query.filters };
      result.aggregations = { ...result.aggregations, ...query.aggregations };

      return result;
    },
    { filters: {}, aggregations: {} },
  );
};

const momentDurationMap = {
  hourly: 'hours',
  daily: 'days',
  weekly: 'weeks',
  monthly: 'months',
};

export const getTimelineData = ({
  data,
  groupByColumnName,
  startTime,
  endTime,
  noGrouping,
  getColor,
  groupOrder = null,
  valueKey = 'value',
  breakdown = 'daily',
  groupTitleMap = {},
  isCurrency = false,
  currency,
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
  const groupedData = !noGrouping
    ? groupByColumnName === 'platform'
      ? groupByPlatform(data)
      : groupBy(data, groupByColumnName)
    : { [groupByColumnName]: data };
  const groups = Object.keys(groupedData);
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
  const timelineGroupMap = {};
  /*
   * `datasets` contain data as defined in chart.js
   * http://www.chartjs.org/docs/latest/#creating-a-chart
   */
  const datasets = [];
  /*
   * `groupDatasetsMap` contain the same info in `datasets` but indexed
   * by `groupName`
   */
  const groupDatasetsMap = {};
  /*
   * `aggregates` contain the agrregate of values that we got from the
   * server to show legend
   */
  const aggregates = [];
  const groupAggregatesMap = {};

  let csvData = [];
  const csvHeader = ['Date'];
  // eslint-disable-next-line no-unused-vars
  let csvGrandTotal = 0;

  if (data.length === 0 || groups.length === 0) {
    csvData = csvData.concat([csvHeader]);

    return {
      labels: [],
      datasets: [],
      aggregates: [],
      csv: arrayToCsvDataUrl(csvData),
    };
  }

  // populates default data , avoids `if` conditions in next loop
  groups.forEach((groupName) => {
    const groupLabel =
      groupTitleMap[groupName] ||
      globalGroupTitleMap[groupName] ||
      (groupByColumnName === 'platform' ? groupName : titleCase(groupName));

    const dataset = {
      label: groupLabel,
      data: [],
    };

    const aggregate = {
      label: groupLabel,
      value: 0,
    };

    datasets.push(dataset);
    groupDatasetsMap[groupName] = dataset;

    aggregates.push(aggregate);
    groupAggregatesMap[groupName] = aggregate;
  });

  groups.forEach((groupName) => {
    const data = groupedData[groupName];
    const otherGroups = groups.filter((item) => item !== groupName);

    data.forEach((item) => {
      const timestamp = item.timestamp * 1000;
      const groupMap = timelineGroupMap[timestamp] || (timelineGroupMap[timestamp] = {});

      // for platform desktop , there will be multiple values for the same
      // timestamp. If value already exists , add to it
      groupMap[groupName] = (groupMap[groupName] || 0) + item[valueKey];

      otherGroups.forEach((groupName) => {
        groupMap[groupName] = groupMap[groupName] || 0;
      });
    });
  });

  /*
   * Transforms data into the input format for chart.js
   */
  let timestamps = Object.keys(timelineGroupMap).sort((a, b) => {
    return Number(a) - Number(b);
  });
  const groupsCsvData = [];
  let startMs = moment(startTime * 1000);
  let endMs = moment(endTime * 1000);
  const firstMs = Number(timestamps[0]);
  const lastMs = Number(timestamps[timestamps.length - 1]);

  if (breakdown === 'hourly') {
    const firstHour = moment(firstMs).startOf('hour').toDate();
    const lastHour = moment(endTime).startOf('hour').toDate();

    startMs = moment(startMs).startOf('day').toDate();
    endMs = moment().isSame(endMs, 'day')
      ? moment().startOf('hour').toDate()
      : moment(endMs).endOf('day').startOf('hour').toDate();

    if (firstHour > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastHour < endMs) {
      timestamps.push(endMs.getTime());
    }
  } else if (breakdown === 'daily') {
    const firstDayStart = moment(firstMs).startOf('day').toDate();
    const lastDayStart = moment(lastMs).startOf('day').toDate();

    startMs = moment(startMs).startOf('day').toDate();
    endMs = moment(endMs).startOf('day').toDate();

    if (firstDayStart > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastDayStart < endMs) {
      timestamps.push(endMs.getTime());
    }
  } else if (breakdown === 'weekly') {
    // momentjs start of week is sunday, whereas
    // pokedex start of week is monday, so adding 1 day
    const firstWeekStart = moment(firstMs).startOf('isoWeek').toDate();
    const lastWeekStart = moment(lastMs).startOf('isoWeek').toDate();

    startMs = moment(startMs).startOf('isoWeek').toDate();
    endMs = moment(endMs).startOf('isoWeek').toDate();

    if (firstWeekStart > startMs) {
      timestamps.unshift(startMs.getTime());
    }

    if (lastWeekStart < endMs) {
      timestamps.push(endMs.getTime());
    }
  } else if (breakdown === 'monthly') {
    const firstMonthStart = moment(firstMs).startOf('month').toDate().getTime();
    const lastMonthStart = moment(lastMs).startOf('month').toDate().getTime();

    startMs = moment(startMs).startOf('month').toDate();
    endMs = moment(endMs).startOf('month').toDate();

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

    let numPointsGap = moment(timestamp).diff(prevTimestamp, momentDurationMap[breakdown]);

    while (numPointsGap > 1) {
      prevTimestamp = moment(prevTimestamp).add(1, momentDurationMap[breakdown]).toDate().getTime();

      result.push(prevTimestamp);

      numPointsGap--;
    }

    result.push(timestamp);

    return result;
  }, []);

  timestamps.forEach((timestamp, tsIndex) => {
    // if missing value
    if (!timelineGroupMap[timestamp]) {
      // eslint-disable-next-line no-multi-assign
      const groupMap = (timelineGroupMap[timestamp] = {});

      groups.forEach((groupName) => {
        groupMap[groupName] = 0;
      });
    }

    let totalAtTime = 0;

    groups.forEach((groupName) => {
      const groupData = groupDatasetsMap[groupName].data;
      const aggregateData = groupAggregatesMap[groupName];
      const yAxisVal = timelineGroupMap[timestamp][groupName];
      const groupCsvData =
        groupsCsvData[tsIndex] ||
        (groupsCsvData[tsIndex] = [
          moment(timestamp).format(
            breakdown === 'monthly'
              ? 'MMM YYYY'
              : `${dateFormat}${breakdown === 'hourly' ? ' HH:mm' : ''}`,
          ),
        ]);

      // `datasets` variable will get populated due to reference
      groupData.push({
        t: timestamp,
        y: isCurrency
          ? i18CurrencyConversionFromMinorUnitToCommonUnit(yAxisVal, currency)
          : yAxisVal,
      });

      aggregateData.value += yAxisVal;

      totalAtTime += yAxisVal;

      groupCsvData.push(yAxisVal);
    });

    groupsCsvData[tsIndex].push(totalAtTime);

    timestamps[tsIndex] = moment(timestamp);
  });

  csvData = csvData.concat(groupsCsvData);

  aggregates.forEach((aggregate) => {
    csvHeader.push(aggregate.label);

    csvGrandTotal += aggregate.value;
    aggregate.value = isCurrency
      ? i18CurrencyConversionFromMinorUnitToCommonUnit(aggregate.value, currency)
      : aggregate.value;
  });

  csvHeader.push('Amount');

  csvData.unshift(csvHeader);

  let groupOrderMap = null;
  // default sorter is sort by value of the group
  let sorter = (item1, item2) => item2.value - item1.value;

  // if an order of groups is specified as an array, the groups will be
  // sorted in the same order
  if (groupOrder && Array.isArray(groupOrder)) {
    groupOrderMap = groupOrder.reduce((result, groupName, index) => {
      result[groupName] = index;
      return result;
    }, {});

    // use this index as the index of group names not specified in
    // groupOrder, basically the group name would appear at the end
    // of the order
    let outlierIndex = groupOrder.length;

    sorter = (item1, item2) => {
      const item1Label = item1.label.toLowerCase();
      const item2Label = item2.label.toLowerCase();
      const item1Index = isDefined(groupOrderMap[item1Label])
        ? groupOrderMap[item1Label]
        : (groupOrderMap[item1Label] = outlierIndex++);
      const item2Index = isDefined(groupOrderMap[item2Label])
        ? groupOrderMap[item2Label]
        : (groupOrderMap[item2Label] = outlierIndex++);

      return item1Index - item2Index;
    };
  }

  // sorting aggregates by their value in descending order
  const orderedGroups = aggregates.sort(sorter).reduce((result, item, index) => {
    result[item.label] = index;
    item.color = getColor ? getColor(item.label) : colors[index % colors.length];
    return result;
  }, {});

  // sorting datasets according to the order of aggregates
  datasets
    .sort(({ label: label1 }, { label: label2 }) => {
      return orderedGroups[label1] - orderedGroups[label2];
    })
    .forEach((item) => {
      // getting the color assigned in the aggregate value
      const groupColor = aggregates[orderedGroups[item.label]].color;

      item.backgroundColor = groupColor;
      item.borderColor = groupColor;
    });

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
