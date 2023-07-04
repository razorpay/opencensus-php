import moment from 'moment';
import { getStartDateFromDiff } from 'common/utils/rzp-utils';

export const WIDGETS = [
  'order_split',
  'order_split_cumulative',
  'manual_risk_order_split_cumulative',
  'manual_review_order_split',
  'feedback_rate',
  'flagged_reason',
  'cost_saving',
  'intelligence_stat_performance',
  'rto_by_zipcode',
  'rto_by_ip',
  'rto_rate',
  'cod_rate',
  'risky_orders',
  'manual_risk_order_split',
];

export const WIDGETS_MAP = {
  'Feedback Rate': 'feedback_rate',
  'Manual Review Order Split': 'manual_review_order_split',
  'Order Split': 'order_split',
  'Flagged Reason': 'flagged_reason',
  'RTO by Zipcode': 'rto_by_zipcode',
  'RTO by IP': 'rto_by_ip',
  'Cost Saving': 'cost_saving',
  'Intelligence Stat Performance': 'intelligence_stat_performance',
  'Risky Orders': 'risky_orders',
  'COD vs Prepaid Orders': 'cod_rate',
  'RTO Rate': 'rto_rate',
  'Manual Risk Order Split': 'manual_risk_order_split',
};

const LIFETIME_WIDGETS = ['rto_by_ip', 'rto_by_zipcode'];

const DEFAULT_DURATION = [-30, 'days'];

export const getQuery = (widget, aggregation_type, startTime, endTime, additionalInfo = {}) => {
  const widgets = [];
  widgets.push({
    name: widget,
    aggregation_type,
    date_range: {
      from: LIFETIME_WIDGETS.includes(widget) ? 0 : startTime,
      to: LIFETIME_WIDGETS.includes(widget) ? 0 : endTime,
    },
  });

  if (Object.keys(additionalInfo).length !== 0) {
    widgets[0].additional_info = additionalInfo;
  }

  return widgets;
};

export const calculateInitialDateRange = (duration = DEFAULT_DURATION) => {
  const endDate = moment();
  const defaultDiff =
    endDate.unix() -
    endDate
      .clone()
      .add(...duration)
      .unix();
  const startDate = getStartDateFromDiff(defaultDiff, endDate);

  return [startDate.toDate().getTime(), endDate.toDate().getTime()];
};

export const widgetsDataFormatter = (widgetsData) => {
  const updateObj = {};

  widgetsData?.forEach((widgetData) => {
    let widgetName = WIDGETS_MAP[widgetData.name];

    if (
      (widgetName === 'order_split' || widgetName === 'manual_risk_order_split') &&
      widgetData.aggregation_type === 'cumulative'
    ) {
      widgetName = `${widgetName}_cumulative`;
      updateObj[widgetName] = {};
      updateObj[widgetName].data = widgetData[WIDGETS_MAP[widgetData.name]];
      return;
    }

    updateObj[widgetName] = {};

    if (widgetName && WIDGETS.includes(widgetName)) {
      updateObj[widgetName].data = widgetData[widgetName];
      updateObj[widgetName].updatedAt = Number(widgetData.updated_at);
      updateObj[widgetName].loading = false;
    }
  });

  return updateObj;
};

export const lifeTimeWidgetsDataFormatter = (attributeData, widget, widgetData) => {
  const { attribute_value, attribute_type } = attributeData;

  const updateObj = {};
  if (widget && WIDGETS.includes(widget)) {
    updateObj[widget] = {};
    updateObj[widget].data = widgetData?.data.map((item) => {
      if (item[attribute_type] === attribute_value) {
        item.is_blocked = !item.is_blocked;
      }
      return item;
    });
  }
  updateObj[widget].modalLoading = false;
  updateObj[widget].updatedAt = widgetData?.updatedAt;

  return updateObj;
};
