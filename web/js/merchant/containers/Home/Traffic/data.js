import moment from 'moment';

import {
  titleCase,
  arrayToCsvDataUrl,
  paiseToRupees
} from 'rzp/utils/rzp-utils';
import colors from 'rzp/utils/chart/colors.js';
import {
  globalGroupTitleMap,
  groupByPlatform,
  getDefaultPaymentFilter
} from 'rzp/utils/pokedex.js';

const dateFormat = 'Do MMM YYYY';

const groupValues = ['transactionVolume', 'noTransactions'];

const groupMeta = {
  [groupValues[0]]: {
    title: 'By Transaction Volume',
    aggType: 'sum',
    column: 'base_amount',
    groupBy: 'platform',
    isCurrency: true,
    value: groupValues[0],
  },
  [groupValues[1]]: {
    title: 'By Number of Transactions',
    aggType: 'count',
    groupBy: 'platform',
    value: groupValues[1],
  },
};

const getQuery = ({ startTime, endTime, group }) => {
  const meta = groupMeta[group];

  return {
    filters: {
      default: [
        getDefaultPaymentFilter(startTime, endTime)
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

const getPieData = ({
  data,
  groupByColumnName,
  groupTitleMap = {},
  isCurrency,
}) => {
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

  const labels = [],
    datasets = {
                 data: [],
                 backgroundColor: colors,
                 hoverBackgroundColor: colors
               },
    legendData = [];

  let csvHeader = ["", "Amount", '%Split'],
    csvData = [],
    csvGrandTotal = 0;

  const valueReducer = (sum, item) => sum + item.value,
        groupedData  = groupByPlatform(data);

  Object.keys(groupedData)
        // sorting groups by share of contribution in desc order
        .sort((groupName1, groupName2) => {

          let group2Value = groupedData[groupName2],
              group1Value = groupedData[groupName1];

          if (Array.isArray(group2Value)) {
          
            group2Value = groupedData[groupName2]
                        = group2Value.reduce(valueReducer, 0);
          }

          if (Array.isArray(group1Value)) {
          
            group1Value = groupedData[groupName1]
                        = group1Value.reduce(valueReducer, 0);
          }

          return group2Value - group1Value;
        })
        .forEach((groupName, index) => {
          const groupTitle   = groupTitleMap[groupName]       ||
                               globalGroupTitleMap[groupName] ||
                               groupName,
                groupCSVData = [];

          labels.push(groupTitle);
          groupCSVData.push(groupTitle);

          let value        = groupedData[groupName],
              displayValue = isCurrency
                               ? paiseToRupees(value)
                               : value;

          datasets.data.push(displayValue);
          groupCSVData.push(value);

          legendData.push({
            color: colors[index % colors.length],
            label: groupTitle,
            value: displayValue,
          });

          csvData.push(groupCSVData);
          csvGrandTotal += value;
        });

  csvData = csvData.map(row => {

    // calculating %share column
    row.push(`${csvGrandTotal > 0
                  ? (row[1] / csvGrandTotal * 100).toFixed(2)
                  : 0}%`);
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
