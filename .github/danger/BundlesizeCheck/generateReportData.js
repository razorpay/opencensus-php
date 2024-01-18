const { bytesToKB } = require('../utils');

const getUsedThreshold = ({ used, limit }) => {
  if (!used || !parseFloat(limit)) {
    return '-';
  }
  return parseFloat(((used / parseFloat(limit)) * 100).toFixed(2));
};

const generateReportData = ({ bundleObj }) =>
  bundleObj.reduce((accumulator, each) => {
    const {
      gzipSize,
      budget: { limit },
    } = each;
    const sizeInKb = bytesToKB(gzipSize);
    accumulator.push({
      ...each,
      displaySize: `${sizeInKb} Kb`,
      usedThreshold: getUsedThreshold({
        used: sizeInKb,
        limit,
      }),
    });
    return accumulator;
  }, []);

module.exports = generateReportData;
