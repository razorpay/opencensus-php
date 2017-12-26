import moment from 'moment';

import { titleCase, arrayToCsvDataUrl } from 'rzp/utils/rzp-utils';
import colors from './colors';
import { groupBy } from '../pokedex';

const dateFormat = 'Do MMM YYYY';

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
  const groupedData = groupBy(data, groupByColumnName),
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
    const groupLabel = groupTitleMap[groupName] || titleCase(groupName),
      groupColor = colors[index % colors.length];

    const dataset = {
      label: titleCase(groupLabel),
      backgroundColor: groupColor,
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

export const getPieData = ({
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

  const groupedData = groupBy(data, groupByColumnName),
    groups = Object.keys(groupedData),
    labels = [],
    datasets = { data: [], backgroundColor: colors },
    legendData = [];

  groups.forEach((groupName, index) => {
    const groupTitle = groupTitleMap[groupName] || titleCase(groupName);

    labels.push(groupTitle);

    const item = groupedData[groupName][0],
      value =
        typeof valueTransformer === 'function'
          ? valueTransformer(item.value, groupedData, groupName)
          : item.value;

    datasets.data.push(value);

    legendData.push({
      color: colors[index % colors.length],
      label: groupTitle,
      value,
    });
  });

  return {
    labels,
    datasets: [datasets],
    legendData,
    csv: arrayToCsvDataUrl([labels, datasets.data]),
  };
};
