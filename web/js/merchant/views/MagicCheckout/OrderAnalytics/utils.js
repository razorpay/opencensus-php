// eslint-disable-next-line import/no-cycle
import {
  AGGERGATE_OPERATION,
  CHART_COLORS,
  AGGREGATION_TYPES,
  CHART_LABEL_MAPPING,
  DEFAULT_CHART_OPTIONS,
  LINE_CHARTS,
  LINE_CHART_GRAPH_COLOR,
  METRIC_TYPE,
  TOOLTIP_HANDLERS,
  UTM_KEYS,
} from './constants';
import { paiseToRupees } from 'common/utils/rzp-utils';
import { i18HumanReadableCurrency, i18HumanReadableNumerals } from 'common/utils/numerals';
import { deepMerge } from 'common/utils/immutable';
import cloneDeep from 'lodash/cloneDeep';
import moment from 'moment';

/**
 * Function to format analytics api response - mainly formats prepaid vs cod data to create two separate metrics
 * used to render the Prepaid vs COD sales & total orders charts
 * @param {object} data - analytics api response
 * @return {object} formatted api respose
 *
 */
export const formatAnalyticsResponse = (data) => {
  data.metrics[CHART_LABEL_MAPPING.SALES_SPLIT] = {
    ...data.metrics?.[CHART_LABEL_MAPPING.ORDER_SALES_SPLIT]?.total_sales,
    timestamps: data.metrics?.[CHART_LABEL_MAPPING.ORDER_SALES_SPLIT]?.timestamps,
    title: 'Prepaid vs COD - Total Sales',
  };
  data.metrics[CHART_LABEL_MAPPING.SALES_SPLIT].values = data.metrics[
    CHART_LABEL_MAPPING.SALES_SPLIT
  ].values.map((val) => ({
    ...val,
    label: `Total Sales - ${val.label}`,
  }));
  data.metrics[CHART_LABEL_MAPPING.ORDER_SPLIT] = {
    ...data.metrics?.[CHART_LABEL_MAPPING.ORDER_SALES_SPLIT]?.total_orders_placed,
    timestamps: data.metrics?.[CHART_LABEL_MAPPING.ORDER_SALES_SPLIT]?.timestamps,
    title: 'Prepaid vs COD - Total Orders',
  };
  data.metrics[CHART_LABEL_MAPPING.ORDER_SPLIT].values = data.metrics[
    CHART_LABEL_MAPPING.ORDER_SPLIT
  ].values.map((val) => ({
    ...val,
    label: `Total Orders - ${val.label}`,
  }));
  return data;
};

/**
 * Function to get chartjs dataset item
 * @param {array} values - array of numbers to plot on chart (usually prices or order count)
 * @param {label} string - label for chart tooltip
 * @param {unit} string - unit for conversion incase of prices
 * @return {object} dataset item
 *
 */
const getDatasetItem = (values, label, unit) => {
  const datasetObj = { label, backgroundColor: CHART_COLORS[label] };
  if (LINE_CHARTS.includes(label)) {
    datasetObj.borderColor = CHART_COLORS[label];
    datasetObj.fill = 'origin';
    datasetObj.borderWidth = 2;
    datasetObj.backgroundColor = (context) => {
      const chart = context.chart;
      const { ctx, chartArea } = chart;

      if (!chartArea) {
        // This case happens on initial chart load
        return null;
      }
      const gradient = ctx.createLinearGradient(0, 0, 0, 200);
      gradient.addColorStop(0, LINE_CHART_GRAPH_COLOR[label]);
      gradient.addColorStop(1, '#FEFFFE');

      return gradient;
    };
  }
  datasetObj.data = unit === 'paise' ? values.map((val) => paiseToRupees(val)) : values;
  return {
    ...datasetObj,
    // tension - creates graphs with curves, pointStyle & pointRadius values are set to remove points when chart is rendered
    tension: 0.4,
    pointStyle: false,
    pointRadius: 0,
  };
};

/**
 * Function to get chartjs dataset item
 * @param {array} values - array of numbers or objects incase of stacked charts
 * @param {widgetName} string - name of widget
 * @param {isChartStacked} boolean - true incase of stacked charts - true when more than one object is present in values
 * @param {unit} string - unit for conversion incase of prices
 * @return {object} dataset item
 */

export const getChartDatasets = (values, widgetName, isChartStacked, unit) => {
  const datasets = [];
  const rawValues = isChartStacked ? values : [{ values, label: widgetName }];
  rawValues.forEach((item) => {
    const datasetObj = getDatasetItem(item.values, item.label, unit);
    datasets.push(datasetObj);
  });
  return datasets;
};

const sum = (arr) => arr.reduce((a, b) => a + b, 0);

/**
 * Aggregates UTM data to display the Traffic sources table. Creates an object with data based on sources, campaigns & medium
 * @param {values} values - array of objects containing order_count, sales & traffic sources in a nested manner
 * @return {object} - contains utm data based on sources, campaigns & medium
 */

export const aggregateUTMData = (values) => {
  const getTotalOrders = (group, start = 0) => {
    return group.reduce((acc, item) => (acc += item.order_count), start);
  };
  const getDataItem = (val, totalOrders) => {
    return {
      label: val.label,
      order_count: val.order_count,
      sales: val.sales,
      total_order_count: totalOrders,
    };
  };
  const data = {
    sources: [],
    mediums: [],
    campaigns: [],
  };
  const totalOrders = getTotalOrders(values, 0);
  const sourcesList = [];
  const mediumsList = [];
  values.forEach((sval) => {
    data.sources.push(getDataItem(sval, totalOrders));
    const mediums = sval[UTM_KEYS.MEDIUM];
    if (mediums.length) {
      sourcesList.push(sval.label);
      mediums.forEach((mval) => {
        data.mediums.push({ ...getDataItem(mval, totalOrders), source: sval.label });
        const campaigns = mval[UTM_KEYS.CAMPAIGN];
        if (campaigns.length) {
          mediumsList.push(mval.label);
          campaigns.forEach((cval) =>
            data.campaigns.push({
              ...getDataItem(cval, totalOrders),
              source: sval.label,
              medium: mval.label,
            }),
          );
        }
      });
    }
  });

  const sourcesWithoutMedium = data.sources.filter((s) => {
    return !sourcesList.includes(s.label);
  });

  const mediumsWithoutCampaign = data.mediums.filter((m) => {
    return !mediumsList.includes(m.label);
  });

  data.mediums.push(...sourcesWithoutMedium);
  data.campaigns.push(...sourcesWithoutMedium, ...mediumsWithoutCampaign);
  return data;
};

/**
 *  Returns aggregated values based on type & operation
 * @param {array} values - array of numbers
 * @param {string} type - can be currency or order count
 * @param {string} operation - aggregate based on average or sum
 * @return {string} - formatted human readable numeric value of the resulting aggregation
 */

export const getAggregatedValue = (values, type, operation) => {
  const sumOfValues = sum(values);
  const val =
    operation === AGGERGATE_OPERATION.AVERAGE
      ? (sumOfValues / values.length).toFixed(2)
      : sumOfValues;
  if (type === METRIC_TYPE.CURRENCY) {
    const CURRENCY = 'INR';
    const convertedAmount = paiseToRupees(val);
    return i18HumanReadableCurrency(convertedAmount, CURRENCY);
  } else {
    return i18HumanReadableNumerals(val);
  }
};

const extendScales = (options, customAxes) => {
  const extendedScales = cloneDeep(options.scales);
  if (extendedScales.yAxes) {
    const axis = customAxes.yAxes[0];
    if (axis.ticks) {
      extendedScales.yAxes[0].ticks = { ...extendedScales.yAxes[0].ticks, ...axis.ticks };
    }
    if (axis.gridLines) {
      extendedScales.yAxes[0].gridLines = {
        ...extendedScales.yAxes[0].gridLines,
        ...axis.gridLines,
      };
    }
  }
  return extendedScales;
};

const extendTooltip = (options, customTooltip) => {
  return deepMerge(options.tooltips, customTooltip);
};

export const getXAxisTickLabel = (aggregation) => {
  if (aggregation === AGGREGATION_TYPES.DAILY) {
    return (val) => val.format('MMM D');
  }
  return (val) => val.format('hh:mm A');
};

export const getChartOptions = (
  customOptions = {},
  aggregation = AGGREGATION_TYPES.DAILY,
  extend,
) => {
  const xAxisTick = getXAxisTickLabel(aggregation);
  if (!extend) return customOptions;
  const options = cloneDeep(DEFAULT_CHART_OPTIONS);
  options.scales.xAxes[0].ticks.callback = xAxisTick;
  if (customOptions.scales) {
    options.scales = extendScales(options, customOptions.scales, aggregation);
  }
  if (customOptions.tooltips) {
    options.tooltips = extendTooltip(options, customOptions.tooltips);
  }
  return options;
};

export const getCurrencyValue = (value) => {
  return i18HumanReadableCurrency(value, 'INR');
};

export const getNumericValue = (value) => {
  return i18HumanReadableNumerals(value);
};

export const customTooltip = (tooltipModel, ctx, chartName) => {
  // Tooltip Element
  let tooltipEl = document.getElementById('chartjs-tooltip');

  // Create element on first render
  if (!tooltipEl) {
    tooltipEl = document.createElement('div');
    tooltipEl.id = 'chartjs-tooltip';
    document.body.appendChild(tooltipEl);
  }

  // Hide if no tooltip
  if (tooltipModel.opacity === 0) {
    tooltipEl.style.opacity = 0;
    return;
  }
  tooltipEl.style.zIndex = 2;

  const { dataPoints, title, labelColors, body } = tooltipModel;
  const getLabel = (index) => body[index]?.lines[0].split(':')[0];
  const getValue = (label, value) => TOOLTIP_HANDLERS[label].value(value);
  let innerHTML = `
      <div class="magic-tooltip-wrapper">
      <div class="tooltip-date">${moment(+title[0]).format('MMM D YYYY hh:mm A')}</div>
      `;
  dataPoints.forEach((item, index) => {
    const label = dataPoints.length > 1 ? getLabel(index) : TOOLTIP_HANDLERS[chartName].label;
    const value =
      dataPoints.length > 1
        ? getValue(label, item.yLabel)
        : TOOLTIP_HANDLERS[chartName].value(item.yLabel);
    const color =
      typeof labelColors[index].backgroundColor === 'string'
        ? labelColors[index].backgroundColor
        : labelColors[index].borderColor;
    innerHTML += `
        <div class="magic-tooltip-info">
          <div class="magic-tooltip-visual">
            <span class="tooltip-graph-color" style="background-color: ${color}"></span>
             <p class="tooltip-graph-label"> ${label}
           </p>
          </div>
          <div class="tooltip-graph-value"> ${value}</div>
        </div>
        `;
  });
  tooltipEl.innerHTML = `${innerHTML}</div>`;

  const tooltipWidth = tooltipEl.clientWidth;
  const tooltipHeight = tooltipEl.clientHeight;
  const tooltipLeft = tooltipModel.caretX - tooltipWidth / 2;
  const tooltipRight = tooltipModel.caretX + tooltipWidth / 2;
  const {
    left: chartLeft,
    right: chartRight,
    top: chartTop,
  } = ctx._chart.canvas.getBoundingClientRect();

  const chartPanel = document.querySelector('.chart-item');
  const { left: panelLeft } = chartPanel.getBoundingClientRect();

  let leftStyle = chartLeft + window.pageXOffset + tooltipModel.caretX;
  const topStyle = chartTop + tooltipModel.caretY + window.pageYOffset - tooltipHeight;

  // Display, position, and set styles for font
  if (leftStyle - tooltipWidth / 2 < panelLeft) {
    const diff = chartLeft - tooltipLeft;
    leftStyle = tooltipModel.caretX + diff;
  } else if (leftStyle + tooltipWidth / 2 > chartRight) {
    const diff = tooltipRight - chartRight;
    leftStyle = tooltipModel.caretX - diff;
  }
  tooltipEl.style.cssText = `
    position: absolute;
    opacity: 1;
    pointer-events: none;
    top: ${topStyle}px;
    left: ${leftStyle}px;
  `;
};

export const getOrderPercentage = (item) =>
  ((item?.order_count / item?.total_order_count) * 100).toFixed(2);
