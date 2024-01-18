const micromatch = require('micromatch');
const { bytesToKB } = require('../utils');

const getResult = (statDetails, budget) => {
  const { limit } = budget;
  const { gzipSize } = statDetails;
  return parseFloat(limit) > bytesToKB(gzipSize);
};

const getMatchedBundles = (budget, stats) => {
  return stats.reduce((accumulator, each) => {
    budget.forEach((eachBudget) => {
      if (micromatch.isMatch(each.label, eachBudget.path)) {
        const { groups, ...rest } = each;
        const { path, ...restBudget } = eachBudget;
        accumulator.push({
          ...rest,
          result: getResult(rest, restBudget),
          budget: {
            ...restBudget,
          },
        });
      }
    });
    return accumulator;
  }, []);
};

const validateStatsToThreshold = ({ budgetConfig, bundleStats }) => {
  return getMatchedBundles(budgetConfig, bundleStats);
};

module.exports = validateStatsToThreshold;
