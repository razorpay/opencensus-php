import React, { Component } from 'react';
import Chart from 'chart.js';
import { chartColors } from 'common/chart';

export class Single extends Component {
  render() {
    let { title, value } = this.props;

    return (
      <div>
        <header>{title}</header>
        {value}
      </div>
    );
  }
}

/**
 * Options to apply to all Line Charts
 */
const lineChartDefaultOptions = {
  lineTension: 0,
  pointRadius: 0,
  fill: false,
};

/**
 * ChartView class
 * @prop {String} title - Title of the chart
 * @prop {Object} data - Data for the charting library
 * @prop {Object} options - Options for the charting library
 * @prop {String} type - Type of the chart (bar, line, pie, etc.)
 */
export class ChartView extends Component {
  render() {
    let { title, data, options, type } = this.props;

    if (type === 'line') {
      // If the type of chart is a line, the colors need to be strings
      data.datasets = data.datasets.map((dset, i) => {
        if (!dset.backgroundColor) {
          dset.backgroundColor = chartColors[i % chartColors.length];
        }
        if (!dset.borderColor) {
          dset.borderColor = chartColors[i % chartColors.length];
        }

        // Merge data, colors, and defult line-chart options
        return {
          ...dset,
          ...lineChartDefaultOptions,
        };
      });
    } else {
      // If the type of chart is not a line, the colors can be lists
      data.datasets.forEach(function(dset) {
        if (!dset.backgroundColor) {
          dset.backgroundColor = chartColors;
        }
        if (!dset.borderColor) {
          dset.borderColor = chartColors;
        }
      });
    }

    return (
      <div>
        <header>{title}</header>
        <canvas ref={el => el && data && makeChart(el, data, options, type)} />
      </div>
    );
  }
}

/**
 * Method to create a Chart
 * @param {DOMElement} el - Container element for the chart
 * @param {Object} data - Data for the chart
 * @param {Object} options - Options to be passed to the charting library
 * @param {String} type - Type of char (bar, line, pie, etc.)
 */
function makeChart(el, data, options, type) {
  return new Chart(el, {
    type,
    data,
    options,
  });
}

/**
 * Method to get a function that returns a tooltip label by appending the suffix onto `yLabel`
 * @param {String} suffix
 * @return {Function}
 */
export function tooltipYLabelSuffix(suffix = '') {
  return function(tooltipItems, data) {
    const { datasetIndex, yLabel } = tooltipItems,
      xLabel = data.datasets[datasetIndex].label;
    return xLabel + ': ' + yLabel + suffix;
  };
}

/**
 * Method to get a function that returns the tooltip label by prepending the prefix onto `yLabel`
 * @param {String} prefix
 * @return {Function}
 */
export function tooltipYLabelPrefix(prefix = '') {
  return function(tooltipItems, data) {
    const { datasetIndex, yLabel } = tooltipItems,
      xLabel = data.datasets[datasetIndex].label;
    return xLabel + ': ' + prefix + yLabel;
  };
}

/**
 * Method that returns the options to be used with the chart
 * @param {String} type - Type of chart
 * @param {Object} extra - Extra parameters, used to override the default ones
 * @return {Object}
 */
export function getPriceChartOptions(type, extra = {}) {
  return {
    legend: {
      display: true,
    },
    ...extra,
  };
}
