const { setOutput } = require('@actions/core');
const { getStatus, showThreshold } = require('../utils');

const Border = '-';
const Cell = '=';
const TableBg = '```';
const tableHead = ['Status', 'Path', 'New Size', 'Max Size', 'Used Limit'];

const getMappedvalues = (data) => {
  return [
    getStatus(data.result, false),
    data.budget.name,
    data.displaySize,
    data.budget.limit || '-',
    showThreshold(data),
  ];
};

const getTableData = ({ reportData, maxPad }) => {
  return reportData.reduce((accumulator, data) => {
    const values = getMappedvalues(data);
    accumulator.push(
      `${values.reduce((acc, each, index) => {
        acc += ` ${Cell.padEnd(maxPad[tableHead[index]] + 3, Cell)}`;
        return acc;
      }, '')}`,
    );
    accumulator.push(
      `|${values.reduce((acc, each, index) => {
        acc += ` ${each.padEnd(maxPad[tableHead[index]] + 2)}|`;
        return acc;
      }, '')}`,
    );
    return accumulator;
  }, []);
};

const getMaxPad = ({ reportData }) => {
  return reportData.reduce(
    (accumulator, each) => {
      const rowData = getMappedvalues(each);
      rowData.forEach((each, index) => {
        if (each.length > accumulator[tableHead[index]]) {
          accumulator[tableHead[index]] = each.length;
        }
      });
      return accumulator;
    },
    tableHead.reduce((acc, each) => {
      acc[each] = each.length;
      return acc;
    }, {}),
  );
};

const getSlackResponse = ({ reportData }) => {
  const maxPad = getMaxPad({ reportData });
  const borderLine = tableHead.reduce((acc, each) => {
    acc += ` ${Border.padEnd(maxPad[each] + 3, Border)}`;
    return acc;
  }, '');
  const tableHeading = tableHead.reduce((acc, each) => {
    acc += ` ${each.padEnd(maxPad[each] + 2)}|`;
    return acc;
  }, '|');
  const tableBody = [
    borderLine,
    tableHeading,
    ...getTableData({ reportData, maxPad }),
    borderLine,
  ].join('\\n');
  return `${TableBg}${tableBody}${TableBg}`;
};

const generateSlackResponse = ({ reportData }) => {
  setOutput('reportTable', getSlackResponse({ reportData }));
};

module.exports = generateSlackResponse;
