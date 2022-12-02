import {
  arrayToCsvDataUrl,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
} from 'common/utils/rzp-utils';
// eslint-disable-next-line import/extensions
import colors from 'common/utils/chart/colors.js';
import {
  globalGroupTitleMap,
  groupByPlatform,
  getDefaultPaymentFilter,
  // eslint-disable-next-line import/extensions
} from 'common/utils/pokedex.js';

const groupValues = ['transactionVolume', 'noTransactions'];

const groupMeta = {
  [groupValues[0]]: {
    title: 'By Payment Volume',
    aggType: 'sum',
    column: 'base_amount',
    groupBy: 'platform',
    isCurrency: true,
    value: groupValues[0],
  },
  [groupValues[1]]: {
    title: 'By Number of Payments',
    aggType: 'count',
    groupBy: 'platform',
    value: groupValues[1],
  },
};

const getQuery = ({ startTime, endTime, group }) => {
  const meta = groupMeta[group];

  return {
    filters: {
      default: [getDefaultPaymentFilter(startTime, endTime)],
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

const getPieData = ({ data, getColor, groupTitleMap = {}, isCurrency, currency }) => {
  /*
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

  const labels = [];
  const datasets = {
    data: [],
  };
  const legendData = [];

  const csvHeader = ['', 'Amount', '%Split'];
  let csvData = [];
  let csvGrandTotal = 0;

  const valueReducer = (sum, item) => sum + item.value;
  const groupedData = groupByPlatform(data);
  const colorsToBeUsed = [];

  Object.keys(groupedData)
    .map((groupName) => {
      groupedData[groupName] = groupedData[groupName].reduce(valueReducer, 0);
      return groupName;
    })
    // sorting groups by share of contribution in desc order
    .sort((groupName1, groupName2) => {
      const group2Value = groupedData[groupName2];
      const group1Value = groupedData[groupName1];

      return group2Value - group1Value;
    })
    .forEach((groupName, index) => {
      const groupTitle = groupTitleMap[groupName] || globalGroupTitleMap[groupName] || groupName;
      const groupCSVData = [];
      const color = getColor ? getColor(groupTitle) : colors[index % colors.length];

      labels.push(groupTitle);
      groupCSVData.push(groupTitle);

      const value = groupedData[groupName];
      const displayValue = isCurrency
        ? i18CurrencyConversionFromMinorUnitToCommonUnit(value, currency)
        : value;

      datasets.data.push(displayValue);
      groupCSVData.push(value);

      legendData.push({
        color,
        label: groupTitle,
        value: displayValue,
      });

      colorsToBeUsed.push(color);

      csvData.push(groupCSVData);
      csvGrandTotal += value;
    });

  datasets.backgroundColor = colorsToBeUsed;
  datasets.hoverBackgroundColor = colorsToBeUsed;

  csvData = csvData.map((row) => {
    // calculating %share column
    row.push(`${csvGrandTotal > 0 ? ((row[1] / csvGrandTotal) * 100).toFixed(2) : 0}%`);
    return row;
  });

  csvData.unshift(csvHeader);

  return {
    labels,
    datasets: [datasets],
    legendData,
    csv: arrayToCsvDataUrl(csvData),
  };
};

export { groupValues, groupMeta, getQuery, getPieData };
