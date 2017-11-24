import { groupBy } from '../pokedex';

export const getTimelineData = (
  data,
  groupByColumnName,
  groupTitleMap = {},
  valueTransformer = null
) => {
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
   */

  const groupedData = groupBy(data, groupByColumnName),
    groups = Object.keys(groupedData),
    /* `groupTimelineMap` is like
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
         * timestamp , else 0 will be put
         */
    groupTimelineMap = {},
    groupDatasetsMap = {},
    datasets = [];

  // populates default data , avoids `if` conditions in next loop
  groups.forEach(groupName => {
    groupTimelineMap[groupName] = {};

    const dataset = {
      title: groupTitleMap[groupName] || groupName,
      data: [],
    };

    datasets.push(dataset);
    groupDatasetsMap[groupName] = dataset;
  });

  /*
   * calculates x-axis labels based on data from all the groups, fills
   * missing data
   */
  groups.forEach(groupName => {
    const title = groupTitleMap[groupName] || groupName,
      data = groupedData[groupName],
      timelineMap = groupTimelineMap[groupName],
      otherGroups = groups.filter(item => item !== groupName),
      labels = [],
      values = [];

    data.forEach(item => {
      const timestamp = item.timestamp * 1000;

      timelineMap[timestamp] =
        typeof valueTransformer === 'function'
          ? valueTransformer(item.value, item, groupName)
          : item.value;

      otherGroups.forEach(groupName => {
        const gData = groupTimelineMap[groupName];

        gData[timestamp] = gData[timestamp] || 0;
      });
    });
  });

  /*
   * Transforms data into the input format for chart.js
   */
  const timestamps = Object.keys(groupTimelineMap).sort();

  timestamps.forEach(timestamp => {
    groups.forEach(groupName => {
      const groupData = groupDatasetsMap[groupName].data;

      // datasets variable will get populated due to reference
      groupData.push(groupTimelineMap[timestamp][groupName]);
    });
  });

  return {
    label: timestamps,
    datasets,
  };
};

export const getPieData = (
  data,
  groupByColumnName,
  groupTitleMap = {},
  valueTransformer = null
) => {
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
    groups = Object.keys(getPieData),
    labels = [],
    datasets = { data: [] };

  groups.forEach(groupName => {
    labels.push(groupTitleMap[groupName] || groupName);

    const item = groupedData[groupName],
      value =
        typeof valueTransformer === 'function'
          ? valueTransformer(value, groupedData, groupName)
          : value;

    datasets.data.push(value);
  });

  return {
    label: labels,
    datasets,
  };
};
