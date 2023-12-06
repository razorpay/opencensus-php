import { set, merge } from 'common/utils/immutable';
import {
  initialFilters,
  getOverallCRData,
  getMethodLevelCRData,
  methodLevelSplit,
  transformMethodLevelTransactionsData,
  getIndustryOverallCRData,
  processOverallCrData,
  getMethodLevelTransactionData,
  getTotalGmv,
  getAmountInCrores,
  getTotalMethodLevelGmv,
  transformMethodLevelGmvData,
} from 'merchant/views/PaymentMetrics/helpers';
import {
  CHART_INITIAL_DATA,
  CHART_NAME_MAP,
  GRAPH_INTERVALS_MAP,
} from 'merchant/views/PaymentMetrics/constants';

const UPDATE_DATE_RANGE = 'UPDATE_DATE_RANGE';
const UPDATE_INTERVAL = 'UPDATE_INTERVAL';
const OVERALL_CR = 'OVERALL_CR';
const INDUSTRY_OVERALL_CR = 'INDUSTRY_OVERALL_CR';
const METHOD_LEVEL_CR = 'METHOD_LEVEL_CR';
const SET_SELECTED_METRIC = 'SET_SELECTED_METRIC';
const SELECTED_METRICS_UPDATE_DATE_RANGE = 'SELECTED_METRICS_UPDATE_DATE_RANGE';
const SELECTED_METRICS_UPDATE_INTERVAL = 'SELECTED_METRICS_UPDATE_INTERVAL';
const RESET_PAYMENT_DASHBOARD = 'RESET_PAYMENT_DASHBOARD';
const METHOD_LEVEL_TRANSACTIONS = 'METHOD_LEVEL_TRANSACTIONS';
const CHECKOUT_GMV = 'CHECKOUT_GMV';
const CHECKOUT_METHOD_LEVEL_GMV = 'CHECKOUT_METHOD_LEVEL_GMV';

export const getOverallCR =
  ({ lte, gte, breakdown }) =>
  async (dispatch) => {
    dispatch({
      type: `${OVERALL_CR}::PENDING`,
      payload: {
        chart: CHART_NAME_MAP[OVERALL_CR],
      },
    });
    let result = {};
    try {
      result = await getOverallCRData({ lte, gte, breakdown });
    } catch (error) {
      result = error;
    }
    let error = '';

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
      const datasets = processOverallCrData(result, lte, gte, breakdown, OVERALL_CR);

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
    let result;
    try {
      result = await getMethodLevelCRData({ lte, gte, breakdown });
    } catch (error) {
      result = error;
    }
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
      data = result.data?.checkout_method_level_overall_cr?.result || [];
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

export const getIndustryOverallCR =
  ({ lte, gte, breakdown, category }) =>
  async (dispatch) => {
    dispatch({
      type: `${INDUSTRY_OVERALL_CR}::PENDING`,
      payload: {
        chart: CHART_NAME_MAP[INDUSTRY_OVERALL_CR],
      },
    });
    let result = {};
    try {
      result = await getIndustryOverallCRData({ lte, gte, breakdown, category });
    } catch (error) {
      result = error;
    }

    let error = '';

    if (result.data?.ERROR || result.errors) {
      error = result.data?.ERROR || result.errors?.[0];
      dispatch({
        type: `${INDUSTRY_OVERALL_CR}::ERROR`,
        payload: {
          chart: CHART_NAME_MAP[INDUSTRY_OVERALL_CR],
          error,
        },
      });
    } else {
      const datasets = processOverallCrData(result, lte, gte, breakdown, INDUSTRY_OVERALL_CR);
      dispatch({
        type: `${INDUSTRY_OVERALL_CR}::SUCCESS`,
        payload: {
          chart: CHART_NAME_MAP[INDUSTRY_OVERALL_CR],
          data: datasets,
        },
      });
    }
  };

export const getMethodLevelTransactions =
  ({ lte, gte, breakdown }) =>
  async (dispatch) => {
    dispatch({
      type: `${METHOD_LEVEL_TRANSACTIONS}::PENDING`,
      payload: {
        chart: CHART_NAME_MAP[METHOD_LEVEL_TRANSACTIONS],
      },
    });
    let error = '';
    let data = [];
    let result;
    try {
      result = await getMethodLevelTransactionData({ lte, gte, breakdown });
    } catch (error) {
      result = error;
    }
    if (result.data?.ERROR || result.errors) {
      error = result.data?.ERROR || result.errors?.[0];
      dispatch({
        type: `${METHOD_LEVEL_TRANSACTIONS}::ERROR`,
        payload: {
          chart: CHART_NAME_MAP[METHOD_LEVEL_TRANSACTIONS],
          error,
        },
      });
    } else {
      data = result.data?.method_level_transactions?.result || [];
      if (data.length) {
        data = transformMethodLevelTransactionsData({ dataList: data, lte, gte, breakdown });
      }

      dispatch({
        type: `${METHOD_LEVEL_TRANSACTIONS}::SUCCESS`,
        payload: {
          chart: CHART_NAME_MAP[METHOD_LEVEL_TRANSACTIONS],
          data,
        },
      });
    }
  };

export const getOverallGmv =
  ({ lte, gte, breakdown }) =>
  async (dispatch) => {
    dispatch({
      type: `${CHECKOUT_GMV}::PENDING`,
      payload: {
        chart: CHART_NAME_MAP[CHECKOUT_GMV],
      },
    });
    let result = {};
    try {
      result = await getTotalGmv({ lte, gte, breakdown });
    } catch (error) {
      result = error;
    }
    let error = '';

    if (result.data?.ERROR || result.errors) {
      error = result.data?.ERROR || result.errors?.[0];
      dispatch({
        type: `${CHECKOUT_GMV}::ERROR`,
        payload: {
          chart: CHART_NAME_MAP[CHECKOUT_GMV],
          error,
        },
      });
    } else {
      const datasets = processOverallCrData(result, lte, gte, breakdown, CHECKOUT_GMV);

      dispatch({
        type: `${CHECKOUT_GMV}::SUCCESS`,
        payload: {
          chart: CHART_NAME_MAP[CHECKOUT_GMV],
          data: getAmountInCrores(datasets),
        },
      });
    }
  };

export const getMethodLevelGmv =
  ({ lte, gte, breakdown }) =>
  async (dispatch) => {
    dispatch({
      type: `${CHECKOUT_METHOD_LEVEL_GMV}::PENDING`,
      payload: {
        chart: CHART_NAME_MAP[CHECKOUT_METHOD_LEVEL_GMV],
      },
    });
    let error = '';
    let data = [];
    let result;
    try {
      result = await getTotalMethodLevelGmv({ lte, gte, breakdown });
    } catch (error) {
      result = error;
    }
    if (result.data?.ERROR || result.errors) {
      error = result.data?.ERROR || result.errors?.[0];
      dispatch({
        type: `${CHECKOUT_METHOD_LEVEL_GMV}::ERROR`,
        payload: {
          chart: CHART_NAME_MAP[CHECKOUT_METHOD_LEVEL_GMV],
          error,
        },
      });
    } else {
      data = result.data?.checkout_method_level_gmv?.result || [];
      if (data.length) {
        data = transformMethodLevelGmvData({
          dataList: data,
          lte,
          gte,
          breakdown,
          type: CHECKOUT_METHOD_LEVEL_GMV,
        });
      }

      dispatch({
        type: `${CHECKOUT_METHOD_LEVEL_GMV}::SUCCESS`,
        payload: {
          chart: CHART_NAME_MAP[CHECKOUT_METHOD_LEVEL_GMV],
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
    case `${INDUSTRY_OVERALL_CR}::PENDING`:
    case `${METHOD_LEVEL_TRANSACTIONS}::PENDING`:
    case `${CHECKOUT_GMV}::PENDING`:
    case `${CHECKOUT_METHOD_LEVEL_GMV}::PENDING`:
      return merge(state, {
        chartData: {
          ...state.chartData,
          [payload.chart]: { isLoading: true, error: '', datasets: [] },
        },
      });

    case `${OVERALL_CR}::ERROR`:
    case `${METHOD_LEVEL_CR}::ERROR`:
    case `${INDUSTRY_OVERALL_CR}::ERROR`:
    case `${METHOD_LEVEL_TRANSACTIONS}::ERROR`:
    case `${CHECKOUT_GMV}::ERROR`:
    case `${CHECKOUT_METHOD_LEVEL_GMV}::ERROR`:
      return merge(state, {
        chartData: {
          ...state.chartData,
          [payload.chart]: { isLoading: false, error: payload.error, datasets: [] },
        },
      });

    case `${OVERALL_CR}::SUCCESS`:
    case `${METHOD_LEVEL_CR}::SUCCESS`:
    case `${INDUSTRY_OVERALL_CR}::SUCCESS`:
    case `${METHOD_LEVEL_TRANSACTIONS}::SUCCESS`:
    case `${CHECKOUT_GMV}::SUCCESS`:
    case `${CHECKOUT_METHOD_LEVEL_GMV}::SUCCESS`:
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
