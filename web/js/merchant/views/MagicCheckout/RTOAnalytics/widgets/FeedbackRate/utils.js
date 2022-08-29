/**
 * @param {Array} feedbackPercentage feedback object having feedback precentange
 * @returns {object} object of formatted data that can be fed directly to the charts
 */
export function feedbackDataFormatter(feedbackPercentage) {
  const precisedPercentage = parseFloat(feedbackPercentage).toFixed(1);
  const datasets = [
    {
      data: [precisedPercentage, 100 - precisedPercentage],
      borderAlign: 'inner',
      backgroundColor: ['#5A7AAA', '#E8E8E8'],
    },
  ];

  return { datasets };
}
