import { FLAGGEDREASONS_DOUGHNUT_COLORS } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

import { FLAGGED_RULES_MAP } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons/reasons';

/**
 * @param {Array} reasonsList objects containing reason and percentage
 * @returns {Array} array of formatted data objects that can be fed directly to the charts
 */
export function chartsDataFormatter(reasonsList) {
  const newReasonsList =
    reasonsList.length > 4
      ? [...reasonsList.slice(0, 4), getOtherReasonsObj(reasonsList.slice(4))]
      : [...reasonsList];
  const formattedDataList = newReasonsList.map((reason, index) => {
    const labels = [FLAGGED_RULES_MAP[reason.reason], 'others'];
    const datasets = [
      {
        data: [reason.percentage, 100 - reason.percentage],
        borderAlign: 'inner',
        backgroundColor: [FLAGGEDREASONS_DOUGHNUT_COLORS[index], '#e8e8e8'],
      },
    ];
    return { labels, datasets };
  });

  return formattedDataList;
}

/**
 * @param {Array} otherReasonsList reason objects other than the first 4
 * @returns {Object} cumulative object of all remaining reasons
 */
export function getOtherReasonsObj(otherReasonsList) {
  return otherReasonsList.reduce(
    (acc, reason) => {
      return {
        ...acc,
        reason: 'Other reasons',
        percentage: acc.percentage + reason.percentage,
      };
    },
    { percentage: 0 },
  );
}

/**
 * @param {Array} feedbackData feedback object having feedback precentange
 * @returns {object} object of formatted data that can be fed directly to the charts
 */
export function feedbackDataFormatter(feedbackData) {
  const { feedback_percentage } = feedbackData ? feedbackData[0] : { feedback_percentage: 0 };
  const precised_percentage = parseFloat(feedback_percentage).toFixed(1);

  const datasets = [
    {
      data: [precised_percentage, 100 - precised_percentage],
      borderAlign: 'inner',
      backgroundColor: ['#5A7AAA', '#E8E8E8'],
    },
  ];

  return { datasets };
}
