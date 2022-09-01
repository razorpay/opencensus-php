import store from 'merchant/store';
import { merchantFetch, merchantFetchWithContentType } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';
import cloneDeep from 'lodash/cloneDeep';
import lodashset from 'lodash/set';

import {
  initialFilters,
  onFetchSR,
  getMetricsData,
  setBreakdownInterval,
  getErrorMessage,
  getOptimizerFilters,
  getInitialGroupings,
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
  SR_FILTERS,
} from 'merchant/views/Transactions/SuccessRate/constants';

const FETCH_SUCCESS_RATE = 'FETCH_SUCCESS_RATE';
const UPDATE_DATE_RANGE = 'UPDATE_DATE_RANGE';
const SET_ACTIVE_TAB = 'SET_ACTIVE_TAB';
const UPDATE_TAB_DATA = 'UPDATE_TAB_DATA';
const SET_GROUP_TYPE_FILTER = 'SET_GROUP_TYPE_FILTER';
const SET_METRICS_DATA = 'SET_METRICS_DATA';
const FETCH_MERCHANT_ERRORS = 'FETCH_MERCHANT_ERRORS';
const FETCH_INTERVALS = 'FETCH_INTERVALS';
const SET_SELECTED_DROPDOWN_FILTER_OPTIONS = 'SET_SELECTED_DROPDOWN_FILTER_OPTIONS';

export const fetchSuccessRate = (payload, updateDropdownOptions) => async (dispatch) => {
  const { successRate = {}, session } = store?.getState();
  const user = session?.user;
  const { activeTab, metrics, tabs } = successRate;
  const {
    selectedInterval,
    group_by,
    dropdownFilterOptions,
    selectedDropdownFilterOptions,
  } = tabs?.[activeTab];
  let newDropdownFilterOptions = dropdownFilterOptions;
  let newSelectedDropdownFilterOptions = selectedDropdownFilterOptions;
  let newGroupBy = group_by;
  dispatch({
    type: `${FETCH_SUCCESS_RATE}::PENDING`,
    payload: {
      [activeTab === 'Overall' ? 'isLoading' : 'tabLoading']: true,
      isDropdownFilterLoading: updateDropdownOptions,
    },
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

    if (updateDropdownOptions) {
      if (!user?.isOptimizerEnabled) {
        newDropdownFilterOptions = SR_FILTERS?.[activeTab];
        newSelectedDropdownFilterOptions = getInitialGroupings(newDropdownFilterOptions);
        newGroupBy = DEFAULT_GROUP_BY[activeTab];
      } else {
        newDropdownFilterOptions = getOptimizerFilters(data, activeTab);
        newSelectedDropdownFilterOptions = getInitialGroupings(newDropdownFilterOptions);
      }
    }

    const options = {
      data,
      startTime: payload.from,
      endTime: payload.to,
      breakdown: selectedInterval,
      group_by: activeTab === 'Overall' || !user.isOptimizerEnabled ? newGroupBy : 'procurer',
    };

    const res = onFetchSR(options);

    if (activeTab === 'Overall' && data?.groups?.[group_by]) {
      const metricsResult = getMetricsData({
        metrics,
        data,
        payload,
        breakdown: selectedInterval,
        group_by,
      });

      dispatch({
        type: SET_METRICS_DATA,
        payload: metricsResult,
      });
    }

    dispatch({
      type: `${FETCH_SUCCESS_RATE}::SUCCESS`,
      payload: {
        ...tabs[activeTab],
        data,
        error: null,
        fetched: true,
        selectedInterval: setBreakdownInterval(payload.from, payload.to),
        dropdownFilterOptions: newDropdownFilterOptions,
        selectedDropdownFilterOptions: newSelectedDropdownFilterOptions,
        group_by: newGroupBy,
        ...res,
      },
    });
  } catch (error) {
    dispatch({
      type: `${FETCH_SUCCESS_RATE}::ERROR`,
      payload: getErrorMessage(error),
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

export const fetchBreakdownIntervals = (breakdown, payload) => async (dispatch) => {
  const { successRate = {}, session } = store?.getState();
  const user = session?.user;
  const { activeTab, tabs } = successRate;
  const { group_by } = tabs[activeTab];

  dispatch({ type: `${FETCH_INTERVALS}::PENDING` });

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
      breakdown,
      group_by: activeTab === 'Overall' || !user?.isOptimizerEnabled ? group_by : 'procurer',
    };

    const res = onFetchSR(options);

    dispatch({
      type: `${FETCH_INTERVALS}::SUCCESS`,
      payload: {
        ...tabs[activeTab],
        data,
        error: null,
        fetched: true,
        selectedInterval: breakdown,
        ...res,
      },
    });
  } catch (error) {
    dispatch({
      type: `${FETCH_INTERVALS}::ERROR`,
      payload: getErrorMessage(error),
    });
  }
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
    type: UPDATE_TAB_DATA,
    payload: { ...tabClone, ...payload },
  };
};

export const updateGraphInterval = (interval) => {
  const tabClone = getActiveTab();
  return {
    type: UPDATE_TAB_DATA,
    payload: { ...tabClone, selectedInterval: interval },
  };
};

export const setGroupTypeFilter = (groupType) => {
  return {
    type: SET_GROUP_TYPE_FILTER,
    payload: groupType,
  };
};

export const setSelectedDropdownFilterOptions = (option) => {
  const { query } = option;
  const { activeTab, tabs } = store?.getState()?.successRate;
  const { selectedDropdownFilterOptions } = tabs?.[activeTab];
  const indexToUpdate = selectedDropdownFilterOptions?.findIndex(
    (option) => option?.query === query,
  );
  return {
    type: SET_SELECTED_DROPDOWN_FILTER_OPTIONS,
    payload: { indexToUpdate, option },
  };
};

const getInitialState = () => {
  const state = {
    isLoading: true,
    tabLoading: true,
    graphLoading: false,
    isLoadingMerchantErrors: true,
    isDropdownFilterLoading: false,
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
    case `${FETCH_SUCCESS_RATE}::PENDING`: {
      return merge(state, ...payload);
    }

    case `${FETCH_SUCCESS_RATE}::SUCCESS`: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, 'isLoading', false);
      lodashset(stateClone, 'tabLoading', false);
      lodashset(stateClone, 'isDropdownFilterLoading', false);
      lodashset(stateClone, `tabs.${state.activeTab}`, payload);
      return stateClone;
    }

    case `${FETCH_SUCCESS_RATE}::ERROR`: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, 'isLoading', false);
      lodashset(stateClone, 'tabLoading', false);
      lodashset(stateClone, 'isDropdownFilterLoading', false);
      lodashset(stateClone, `tabs.${state.activeTab}.error`, payload);
      return stateClone;
    }

    case `${FETCH_MERCHANT_ERRORS}::PENDING`: {
      return set(state, 'isLoadingMerchantErrors', true);
    }

    case `${FETCH_MERCHANT_ERRORS}::SUCCESS`: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, 'isLoadingMerchantErrors', false);
      lodashset(stateClone, 'merchantErrors', payload?.data);
      return stateClone;
    }

    case `${FETCH_MERCHANT_ERRORS}::ERROR`: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, 'isLoadingMerchantErrors', false);
      lodashset(stateClone, 'merchantErrors', payload?.data);
      return stateClone;
    }

    case `${FETCH_INTERVALS}::PENDING`: {
      return set(state, 'graphLoading', true);
    }

    case `${FETCH_INTERVALS}::SUCCESS`: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, 'graphLoading', false);
      lodashset(stateClone, `tabs.${state.activeTab}`, payload);
      return stateClone;
    }

    case `${FETCH_INTERVALS}::ERROR`: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, 'graphLoading', false);
      lodashset(stateClone, `tabs.${state.activeTab}.error`, payload);
      return stateClone;
    }

    case SET_METRICS_DATA: {
      return set(state, 'metrics', payload);
    }

    case SET_ACTIVE_TAB: {
      return set(state, 'activeTab', payload);
    }

    case UPDATE_DATE_RANGE: {
      return merge(state, {
        filters: {
          ...state.filters,
          ...payload,
        },
      });
    }

    case UPDATE_TAB_DATA: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, `tabs.${state.activeTab}`, payload);
      return stateClone;
    }

    case SET_SELECTED_DROPDOWN_FILTER_OPTIONS: {
      const { indexToUpdate, option } = payload;
      const stateClone = cloneDeep(state);
      lodashset(
        stateClone,
        `tabs.${state.activeTab}.selectedDropdownFilterOptions.${indexToUpdate}`,
        option,
      );
      return stateClone;
    }

    case SET_GROUP_TYPE_FILTER: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, `tabs.${state.activeTab}.group_by`, payload);
      return stateClone;
    }

    default:
      return state;
  }
};
