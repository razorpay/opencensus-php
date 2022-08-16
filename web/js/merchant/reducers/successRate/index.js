import store from 'merchant/store';
import { merchantFetch, merchantFetchWithContentType } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';

import {
  initialFilters,
  onFetchSR,
  metricValues,
} from 'merchant/views/Transactions/SuccessRate/helper';
import {
  DEFAULT_ACTIVE_TAB,
  DEFAULT_GROUP_BY,
  DEFAULT_METHOD,
  tabsOrder,
  tabMeta,
  metricsCard,
  tabsHelpTextMap,
  tabsTitleMap,
} from 'merchant/views/Transactions/SuccessRate/constants';

const FETCH_SUCCESS_RATE = 'FETCH_SUCCESS_RATE';
const UPDATE_DATE_RANGE = 'UPDATE_DATE_RANGE';
const SET_ACTIVE_TAB = 'SET_ACTIVE_TAB';
const UPDATE_TABS = 'UPDATE_TABS';
const UPDATE_GRAPH_INTERVAL = 'UPDATE_GRAPH_INTERVAL';
const SET_METRICS_DATA = 'SET_METRICS_DATA';
const FETCH_MERCHANT_ERRORS = 'FETCH_MERCHANT_ERRORS';

export const fetchSuccessRate = (payload) => async (dispatch) => {
  const { activeTab, metrics, tabs } = store?.getState()?.successRate;
  const { selectedInterval, group_by } = tabs[activeTab];
  dispatch({
    type: `${FETCH_SUCCESS_RATE}::PENDING`,
    key: activeTab === 'Overall' ? 'isLoading' : 'tabLoading',
  });
  try {
    const { data } = await merchantFetch({
      url: 'success-rate/merchant/sr',
      mode: 'live',
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      data: payload,
    });

    if (data?.Code === 'SERVER_ERROR') throw new Error(data?.Description);

    const options = {
      data,
      startTime: payload.from,
      endTime: payload.to,
      breakdown: selectedInterval,
      group_by,
    };

    const res = onFetchSR(options);

    if (activeTab === 'Overall' && data?.groups?.[group_by]) {
      const groups = data.groups?.[group_by];

      tabsOrder.forEach((tabName, tabIdx) => {
        const groupIdx = groups.findIndex((group) => group.name === tabName.toLocaleLowerCase());

        const args = {
          intervals: (tabIdx > 0 ? groups[groupIdx]?.intervals : data?.intervals) ?? [],
          startTime: payload.from,
          endTime: payload.to,
          breakdown: selectedInterval,
          tagIndex: tabIdx > 0 ? groupIdx : 0,
        };

        const vals = metricValues(tabIdx > 0 ? groups[groupIdx] : data, args);
        metrics[tabName] = { ...metrics[tabName], ...vals };
      });

      dispatch({ type: SET_METRICS_DATA, payload: metrics });
    }

    dispatch({
      type: `${FETCH_SUCCESS_RATE}::SUCCESS`,
      payload: {
        ...tabs[activeTab],
        data,
        error: null,
        fetched: true,
        tabLoading: false,
        ...res,
      },
    });
  } catch (error) {
    dispatch({
      type: `${FETCH_SUCCESS_RATE}::ERROR`,
      payload: { error: error?.errors?.[0] ?? error?.message },
    });
  }
};

export const fetchMerchantErrors = (payload) => {
  return {
    type: FETCH_MERCHANT_ERRORS,
    payload: merchantFetchWithContentType({
      url: 'success-rate/merchant/error',
      mode: 'live',
      method: 'POST',
      data: payload,
    }),
  };
};

export const updateDateRange = (payload) => {
  return {
    type: UPDATE_DATE_RANGE,
    payload,
  };
};

export const setActiveTab = (tabName) => {
  return {
    type: SET_ACTIVE_TAB,
    payload: tabName,
  };
};

export const getActiveTab = () => {
  const { activeTab, tabs } = store.getState().successRate;
  return { ...tabs[activeTab] };
};

export const updateGraphData = (payload) => {
  const tabClone = getActiveTab();
  return {
    type: UPDATE_TABS,
    payload: { ...tabClone, ...payload },
  };
};

export const updateGraphInterval = (interval) => {
  const tabClone = getActiveTab();
  return {
    type: UPDATE_TABS,
    payload: { ...tabClone, selectedInterval: interval },
  };
};

const getInitialState = () => {
  const state = {
    isLoading: true,
    tabLoading: true,
    isLoadingMerchantErrors: true,
    activeTab: DEFAULT_ACTIVE_TAB,
    filters: initialFilters(),
    metrics: {},
    tabs: {},
    merchantErrors: {},
  };

  tabsOrder.forEach((tabName) => {
    state.metrics[tabName] = {
      ...metricsCard,
      name: tabName,
      title: tabsTitleMap[tabName],
      helpText: tabsHelpTextMap[tabName],
    };
    state.tabs[tabName] = {
      ...tabMeta,
      name: tabName,
      method: DEFAULT_METHOD[tabName],
      group_by: DEFAULT_GROUP_BY[tabName],
    };
  });

  return state;
};

export default (state = getInitialState(), action) => {
  const { type, payload } = action;

  switch (type) {
    case `${FETCH_SUCCESS_RATE}::PENDING`:
      return set(state, action.key, true);

    case `${FETCH_SUCCESS_RATE}::SUCCESS`:
      return merge(state, {
        isLoading: false,
        tabLoading: false,
        tabs: {
          ...state.tabs,
          [state.activeTab]: payload,
        },
      });

    case `${FETCH_SUCCESS_RATE}::ERROR`:
      return merge(state, {
        isLoading: false,
        tabLoading: false,
        tabs: {
          ...state.tabs,
          [state.activeTab]: { ...state.tabs[state.activeTab], ...payload },
        },
      });

    case `${FETCH_MERCHANT_ERRORS}::PENDING`:
      return set(state, 'isLoadingMerchantErrors', true);

    case `${FETCH_MERCHANT_ERRORS}::SUCCESS`:
      return merge(state, {
        isLoadingMerchantErrors: false,
        merchantErrors: { ...payload?.data },
      });

    case `${FETCH_MERCHANT_ERRORS}::ERROR`:
      return merge(state, {
        isLoadingMerchantErrors: false,
        merchantErrors: {},
      });

    case SET_METRICS_DATA:
      return set(state, 'metrics', payload);

    case SET_ACTIVE_TAB:
      return set(state, 'activeTab', payload);

    case UPDATE_DATE_RANGE:
      return merge(state, {
        filters: {
          ...state.filters,
          ...payload,
        },
      });

    case UPDATE_TABS: {
      return {
        ...state,
        tabs: {
          ...state.tabs,
          [state.activeTab]: payload,
        },
      };
    }

    case UPDATE_GRAPH_INTERVAL: {
      const tabClone = getActiveTab();
      return {
        ...state,
        tabs: {
          ...state.tabs,
          [state.activeTab]: { ...tabClone, selectedInterval: payload },
        },
      };
    }

    default:
      return state;
  }
};
