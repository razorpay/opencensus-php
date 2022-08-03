import moment from 'moment';
import { getStartDateFromDiff } from 'common/utils/rzp-utils';

export const TIMED_WIDGETS = [
  'order_split',
  'flagged_reason',
  'cost_saving',
  'intelligence_stat_performance',
  'feedback_rate',
];

export const WIDGETS = [
  'order_split',
  'order_split_cumulative',
  'feedback_rate',
  'flagged_reason',
  'cost_saving',
  'intelligence_stat_performance',
  'rto_by_zipcode',
  'rto_by_ip',
];

export const WIDGETS_MAP = {
  'Feedback Rate': 'feedback_rate',
  'Order Split': 'order_split',
  'Flagged Reason': 'flagged_reason',
  'RTO by Zipcode': 'rto_by_zipcode',
  'RTO by IP': 'rto_by_ip',
  'Cost Saving': 'cost_saving',
  'Intelligence Stat Performance': 'intelligence_stat_performance',
};

const LIFETIME_WIDGETS = ['rto_by_ip', 'rto_by_zipcode'];

const DEFAULT_DURATION = [-30, 'days'];

const widgetDefaults = {
  order_split: {
    aggregation_type: 'weekly',
    date_range: {},
  },
  feedback_rate: {
    aggregation_type: 'cumulative',
    date_range: {},
  },
  flagged_reason: {
    aggregation_type: 'cumulative',
    date_range: {},
  },
  cost_saving: {
    aggregation_type: 'weekly',
    date_range: {},
  },
  intelligence_stat_performance: {
    aggregation_type: 'weekly',
    date_range: {},
  },
  rto_by_zipcode: {
    aggregation_type: 'lifetime',
    date_range: {
      from: 0,
      to: 0,
    },
  },
  rto_by_ip: {
    aggregation_type: 'lifetime',
    date_range: {
      from: 0,
      to: 0,
    },
  },
};

const addCumulativeHeader = (widgets, startUnix, endUnix) => {
  widgets.push({
    name: 'order_split',
    aggregation_type: 'cumulative',
    date_range: {
      from: startUnix,
      to: endUnix,
    },
  });
};

export const getDefaultQuery = (startTime, endTime) => {
  const startUnix = moment(startTime).unix();
  const endUnix = moment(endTime).unix();
  const queryBody = {};

  const widgets = Object.keys(widgetDefaults).map((widgetName) => {
    return {
      name: widgetName,
      ...widgetDefaults[widgetName],
      date_range: {
        from: LIFETIME_WIDGETS.includes(widgetName) ? 0 : startUnix,
        to: LIFETIME_WIDGETS.includes(widgetName) ? 0 : endUnix,
      },
    };
  });

  // for cumulative header
  addCumulativeHeader(widgets, startUnix, endUnix);

  return { ...queryBody, widgets };
};

export const getTimedQuery = (startTime, endTime) => {
  const startUnix = moment(startTime).add(5, 'hours').add(30, 'minutes').unix();
  let endUnix = moment(endTime).add(5, 'hours').add(30, 'minutes').unix();
  if (endUnix > moment().unix()) {
    endUnix = moment().unix();
  }
  const queryBody = {};

  const widgets = TIMED_WIDGETS.map((widgetName) => {
    return {
      name: widgetName,
      ...widgetDefaults[widgetName],
      date_range: {
        from: startUnix,
        to: endUnix,
      },
    };
  });

  // for cumulative header
  addCumulativeHeader(widgets, startUnix, endUnix);

  return { ...queryBody, widgets };
};

export const calculateInitialDateRange = () => {
  const endDate = moment();
  const defaultDiff =
    endDate.unix() -
    endDate
      .clone()
      .add(...DEFAULT_DURATION)
      .unix();
  const startDate = getStartDateFromDiff(defaultDiff, endDate);

  return [startDate.toDate().getTime(), endDate.toDate().getTime()];
};

export const widgetsDataFormatter = (widgetsData) => {
  const updateObj = {};

  widgetsData?.forEach((widgetData) => {
    let widgetName = WIDGETS_MAP[widgetData.name];

    if (widgetName === 'order_split' && widgetData.aggregation_type === 'cumulative') {
      widgetName = 'order_split_cumulative';
      updateObj[widgetName] = {};
      updateObj[widgetName].data = widgetData.order_split;
      return;
    }

    updateObj[widgetName] = {};
    if (TIMED_WIDGETS.includes(widgetName)) {
      updateObj[widgetName].loading = false;
    }
    if (widgetName && WIDGETS.includes(widgetName)) {
      updateObj[widgetName].data = widgetData[widgetName];
      updateObj[widgetName].updatedAt = Number(widgetData.updated_at);
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
