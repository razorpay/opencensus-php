import moment from 'moment';

import {
  titleCase,
  arrayToCsvDataUrl,
  paiseToRupees
} from 'rzp/utils/rzp-utils';
import colors from 'rzp/utils/chart/colors.js';
import { globalGroupTitleMap, groupByPlatform } from 'rzp/utils/pokedex.js';

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
    title: 'By No. of Transactions',
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
        {
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

  let csvHeader = ['', `Total${isCurrency ? '(Paise)' : ''}`, '%Split'],
    csvFooter = ['Total'],
    csvData = [],
    csvGrandTotal = 0;

  const groupedData = groupByPlatform(data);

  Object.keys(groupedData).forEach((groupName, index) => {
    const groupTitle =
        groupTitleMap[groupName] || globalGroupTitleMap[groupName] || groupName,
      groupCSVData = [];

    labels.push(groupTitle);
    groupCSVData.push(groupTitle);

    let value = groupedData[groupName].reduce(
      (result, item) => result + item.value,
      0
    ),
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

  csvFooter.push(csvGrandTotal);

  csvData.unshift(csvHeader);
  csvData.push(csvFooter);

  return {
    labels,
    datasets: [datasets],
    legendData,
    csv: arrayToCsvDataUrl(csvData),
  };
};

export { groupValues, groupMeta, getQuery, getPieData };
