import moment from 'moment';

import { titleCase, arrayToCsvDataUrl } from 'rzp/utils/rzp-utils';
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
  valueTransformer = null,
}) => {
  /*
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
   */

  const labels = [],
    datasets = { data: [], backgroundColor: colors },
    legendData = [];

  let csvHeader = ['', 'Count', '%Split'],
    csvFooter = ['Total'],
    csvData = [],
    grandTotal = 0;

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
    );

    value =
      typeof valueTransformer === 'function'
        ? valueTransformer(value, groupedData, groupName)
        : value;

    datasets.data.push(value);
    groupCSVData.push(value);

    legendData.push({
      color: colors[index % colors.length],
      label: groupTitle,
      value,
    });

    csvData.push(groupCSVData);
    grandTotal += value;
  });

  csvData = csvData.map(row => {
    row.push(`${grandTotal > 0 ? (row[1] / grandTotal * 100).toFixed(2) : 0}%`);
    return row;
  });

  csvFooter.push(grandTotal);

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
