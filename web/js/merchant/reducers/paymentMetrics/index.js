import { set, merge } from 'common/utils/immutable';
import {
  initialFilters,
  getOverallCRData,
  getTimelineData,
  getMethodLevelCRData,
  methodLevelSplit,
} from 'merchant/views/PaymentMetrics/helpers';
import {
  CHART_INITIAL_DATA,
  CHART_NAME_MAP,
  GRAPHS_DATA,
  defaultTagStyle,
  GRAPH_INTERVALS_MAP,
} from 'merchant/views/PaymentMetrics/constants';

const UPDATE_DATE_RANGE = 'UPDATE_DATE_RANGE';
const UPDATE_INTERVAL = 'UPDATE_INTERVAL';
const OVERALL_CR = 'OVERALL_CR';
const METHOD_LEVEL_CR = 'METHOD_LEVEL_CR';
const SET_SELECTED_METRIC = 'SET_SELECTED_METRIC';
const SELECTED_METRICS_UPDATE_DATE_RANGE = 'SELECTED_METRICS_UPDATE_DATE_RANGE';
const SELECTED_METRICS_UPDATE_INTERVAL = 'SELECTED_METRICS_UPDATE_INTERVAL';
const RESET_PAYMENT_DASHBOARD = 'RESET_PAYMENT_DASHBOARD';

export const getOverallCR =
  ({ lte, gte, breakdown }) =>
  async (dispatch) => {
    dispatch({
      type: `${OVERALL_CR}::PENDING`,
      payload: {
        chart: CHART_NAME_MAP[OVERALL_CR],
      },
    });
    const result = await getOverallCRData({ lte, gte, breakdown });
    let error = '';
    let data = [];
    const datasets = [];
    if (result.data?.ERROR || result.errors) {
      error = result.data?.ERROR || result.errors?.[0];
      dispatch({
        type: `${OVERALL_CR}::ERROR`,
        payload: {
          chart: CHART_NAME_MAP[OVERALL_CR],
          error,
        },
      });
    } else {
      data = (result.data && result.data[CHART_NAME_MAP[OVERALL_CR]]?.result) || [];
      if (data.length) {
        // get missing timestamp data as well as from backend if value 0 they are not sending
        // but for chart to get staring line we need to plot 0 otherwise it will act as dot
        data = getTimelineData({ data, startTime: gte, endTime: lte, breakdown });
        datasets.push({
          label: GRAPHS_DATA[OVERALL_CR].name,
          data,
          fill: false,
          borderWidth: 2,
          borderColor: defaultTagStyle.color,
          backgroundColor: defaultTagStyle.backgroundColor,
          xAxisID: GRAPHS_DATA[OVERALL_CR].xAxisID,
          yAxisID: GRAPHS_DATA[OVERALL_CR].yAxisID,
          tagName: GRAPHS_DATA[OVERALL_CR].name,
        });
      }
      dispatch({
        type: `${OVERALL_CR}::SUCCESS`,
        payload: {
          chart: CHART_NAME_MAP[OVERALL_CR],
          data: datasets,
        },
      });
    }
  };

export const getMethodLevelCR =
  ({ lte, gte, breakdown }) =>
  async (dispatch) => {
    dispatch({
      type: `${METHOD_LEVEL_CR}::PENDING`,
      payload: {
        chart: CHART_NAME_MAP[METHOD_LEVEL_CR],
      },
    });
    let error = '';
    let data = [];
    const result = await getMethodLevelCRData({ lte, gte, breakdown });
    if (result.data?.ERROR || result.errors) {
      error = result.data?.ERROR || result.errors?.[0];
      dispatch({
        type: `${METHOD_LEVEL_CR}::ERROR`,
        payload: {
          chart: CHART_NAME_MAP[METHOD_LEVEL_CR],
          error,
        },
      });
    } else {
      data = result.data?.checkout_method_level_cr?.result || [];
      if (data.length) {
        data = methodLevelSplit({ dataList: data, lte, gte, breakdown });
      }

      dispatch({
        type: `${METHOD_LEVEL_CR}::SUCCESS`,
        payload: {
          chart: CHART_NAME_MAP[METHOD_LEVEL_CR],
          data,
        },
      });
    }
  };

// to update Date Range for chart
export const updateDateRange = (payload) => {
  return {
    type: UPDATE_DATE_RANGE,
    payload,
  };
};

// to update interval (hourly , daily , weekly)
export const updateInterval = (payload) => {
  return {
    type: UPDATE_INTERVAL,
    payload,
  };
};

export const setSelectedMetric = (selectedMetric) => ({
  type: SET_SELECTED_METRIC,
  payload: selectedMetric,
});

export const selectedMetricsUpdateDateRange = (payload) => {
  return {
    type: SELECTED_METRICS_UPDATE_DATE_RANGE,
    payload,
  };
};

export const selectedMetricsUpdateInterval = (payload) => {
  return {
    type: SELECTED_METRICS_UPDATE_INTERVAL,
    payload,
  };
};

export const resetPaymentDashboard = () => {
  return { type: RESET_PAYMENT_DASHBOARD };
};

const initialState = {
  filters: initialFilters(),
  interval: GRAPH_INTERVALS_MAP.hourly,
  chartData: CHART_INITIAL_DATA,
  selectedMetric: '',
  selectedMetricFilters: initialFilters(),
  selectedMetricInterval: GRAPH_INTERVALS_MAP.hourly,
};

export default (state = initialState, action) => {
  const { type, payload } = action;

  switch (type) {
    case UPDATE_DATE_RANGE: {
      return merge(state, {
        filters: {
          ...state.filters,
          ...payload,
        },
      });
    }
    case UPDATE_INTERVAL: {
      return set(state, 'interval', payload);
    }
    case `${OVERALL_CR}::PENDING`:
    case `${METHOD_LEVEL_CR}::PENDING`:
      return merge(state, {
        chartData: {
          ...state.chartData,
          [payload.chart]: { isLoading: true, error: '', datasets: [] },
        },
      });

    case `${OVERALL_CR}::ERROR`:
    case `${METHOD_LEVEL_CR}::ERROR`:
      return merge(state, {
        chartData: {
          ...state.chartData,
          [payload.chart]: { isLoading: false, error: payload.error, datasets: [] },
        },
      });

    case `${OVERALL_CR}::SUCCESS`:
    case `${METHOD_LEVEL_CR}::SUCCESS`:
      return merge(state, {
        chartData: {
          ...state.chartData,
          [payload.chart]: { isLoading: false, error: '', datasets: payload.data },
        },
      });

    case SET_SELECTED_METRIC: {
      return set(state, 'selectedMetric', payload);
    }

    case SELECTED_METRICS_UPDATE_DATE_RANGE: {
      return merge(state, {
        selectedMetricFilters: {
          ...state.selectedMetricFilters,
          ...payload,
        },
      });
    }
    case SELECTED_METRICS_UPDATE_INTERVAL: {
      return set(state, 'selectedMetricInterval', payload);
    }

    case RESET_PAYMENT_DASHBOARD: {
      return merge(state, {
        chartData: CHART_INITIAL_DATA,
      });
    }

    default:
      return state;
  }
};
