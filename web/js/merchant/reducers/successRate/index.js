import moment from 'moment';
import store from 'merchant/store';
import { set, merge } from 'common/utils/immutable';
import cloneDeep from 'lodash/cloneDeep';
import lodashset from 'lodash/set';

import {
  initialFilters,
  onFetchSR,
  getMetricsData,
  getBreakdownInterval,
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
  tabsTitleMap,
  SR_FILTERS,
  CARD,
  NETBANKING,
  EMANDATE,
} from 'merchant/views/Transactions/SuccessRate/constants';
import {
  getSR,
  getResolvedDowntimes,
  getOngoingDowntimes,
  getMerchantError,
} from 'merchant/views/Transactions/SuccessRate/service';

const FETCH_SUCCESS_RATE = 'FETCH_SUCCESS_RATE';
const UPDATE_DATE_RANGE = 'UPDATE_DATE_RANGE';
const SET_ACTIVE_TAB = 'SET_ACTIVE_TAB';
const SET_DEFAULT_INTERVAL = 'SET_DEFAULT_INTERVAL';
const SET_DEFAULT_LAST_UPDATED_AT = 'SET_DEFAULT_LAST_UPDATED_AT';
const UPDATE_TAB_DATA = 'UPDATE_TAB_DATA';
const SET_GROUP_TYPE_FILTER = 'SET_GROUP_TYPE_FILTER';
const SET_METRICS_DATA = 'SET_METRICS_DATA';
const FETCH_MERCHANT_ERRORS = 'FETCH_MERCHANT_ERRORS';
const FETCH_INTERVALS = 'FETCH_INTERVALS';
const SET_SELECTED_DROPDOWN_FILTER_OPTIONS = 'SET_SELECTED_DROPDOWN_FILTER_OPTIONS';
const SET_CARD_TYPE_FILTER = 'SET_CARD_TYPE_FILTER';
const RESET_SR_DASHBOARD = 'RESET_SR_DASHBOARD';
const SET_FAILURE_REASONS_TYPE = 'SET_FAILURE_REASONS_TYPE';
const SEARCH_MERCHANT_ID = 'SEARCH_MERCHANT_ID';

export const fetchSuccessRate =
  ({ payload, updateDropdownOptions, resetSelectedInterval = true, refreshMetricTabs = false }) =>
  async (dispatch) => {
    const { successRate = {}, session } = store?.getState();
    const user = session?.user;
    const { activeTab: stateActiveTab, metrics, tabs } = successRate;
    const activeTab = refreshMetricTabs ? 'Overall' : stateActiveTab;
    const { selectedInterval, group_by, dropdownFilterOptions, selectedDropdownFilterOptions } =
      tabs?.[activeTab];
    const newSelectedInterval = resetSelectedInterval
      ? getBreakdownInterval(moment.unix(payload.from), moment.unix(payload.to))
      : selectedInterval;
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
      const promises = [];

      promises.push(getSR(payload));

      // Fetch downtimes for hourly intervals and for razorpay merchants.
      if (
        newSelectedInterval === 'hourly' &&
        [CARD, NETBANKING, EMANDATE].includes(activeTab) &&
        !refreshMetricTabs &&
        !user?.isOptimizerEnabled
      ) {
        // Taking start date 24hr before endDate as downtime api doesnt support time query params and we show hourly graph if it is <= 24hr.
        const startDate = moment(payload.to * 1000)
          .clone()
          .subtract(24, 'hour')
          .format('YYYY-MM-DD');
        const endDate = moment(payload.to * 1000).format('YYYY-MM-DD');

        const data = {
          skip: '0',
          startDate,
          endDate,
          method: activeTab.toLowerCase(),
        };

        promises.push(getResolvedDowntimes(data), getOngoingDowntimes());
      }

      const [{ data: sr } = {}, { data: resolvedDowntimes } = {}, { data: ongoingDowntimes } = {}] =
        await Promise.all(promises);

      if (sr?.Code === 'SERVER_ERROR') throw new Error(sr?.Description);

      if (updateDropdownOptions) {
        if (!user?.isOptimizerEnabled) {
          newDropdownFilterOptions = SR_FILTERS?.[activeTab];
          newSelectedDropdownFilterOptions = getInitialGroupings(newDropdownFilterOptions);
          newGroupBy = DEFAULT_GROUP_BY[activeTab];
        } else {
          newDropdownFilterOptions = getOptimizerFilters(sr, activeTab);
          newSelectedDropdownFilterOptions = getInitialGroupings(newDropdownFilterOptions);
        }
      }

      const options = {
        data: sr,
        startTime: payload.from,
        endTime: payload.to,
        breakdown: newSelectedInterval,
        group_by: activeTab === 'Overall' || !user.isOptimizerEnabled ? newGroupBy : 'procurer',
        activeTab,
        resolvedDowntimes,
        ongoingDowntimes,
      };

      const res = onFetchSR(options);

      if (activeTab === 'Overall') {
        const metricsResult = getMetricsData({
          metrics,
          data: sr,
          payload,
          breakdown: newSelectedInterval,
          group_by,
        });

        dispatch({
          type: SET_METRICS_DATA,
          payload: metricsResult,
        });
      }

      !refreshMetricTabs &&
        dispatch({
          type: `${FETCH_SUCCESS_RATE}::SUCCESS`,
          payload: {
            ...tabs[activeTab],
            data: sr,
            error: null,
            fetched: true,
            selectedInterval: newSelectedInterval,
            dropdownFilterOptions: newDropdownFilterOptions,
            selectedDropdownFilterOptions: newSelectedDropdownFilterOptions,
            group_by: newGroupBy,
            downtimes: { resolved: resolvedDowntimes || [], ongoing: ongoingDowntimes || [] },
            lastUpdatedAt: moment().unix(),
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

export const setFailureReasonType = (type) => {
  return {
    type: SET_FAILURE_REASONS_TYPE,
    payload: type,
  };
};

export const fetchMerchantErrors = (payload) => {
  return {
    type: FETCH_MERCHANT_ERRORS,
    payload: getMerchantError(payload),
  };
};

export const fetchBreakdownIntervals = (breakdown, payload) => async (dispatch) => {
  const { successRate = {}, session } = store?.getState();
  const user = session?.user;
  const { activeTab, tabs } = successRate;
  const { group_by, selectedTags } = tabs[activeTab];

  dispatch({ type: `${FETCH_INTERVALS}::PENDING` });

  try {
    const { data } = await getSR(payload);

    if (data?.Code === 'SERVER_ERROR') throw new Error(data?.Description);

    const options = {
      data,
      startTime: payload.from,
      endTime: payload.to,
      breakdown,
      group_by: activeTab === 'Overall' || !user?.isOptimizerEnabled ? group_by : 'procurer',
      activeTab,
      selectedTags,
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

export const setDefaultInterval = (interval) => {
  return {
    type: SET_DEFAULT_INTERVAL,
    payload: interval,
  };
};

export const setDefaultLastUpdatedAt = () => {
  return {
    type: SET_DEFAULT_LAST_UPDATED_AT,
    payload: null,
  };
};

export const getActiveTab = () => {
  const { activeTab, tabs = {} } = store?.getState()?.successRate;
  return { ...tabs[activeTab] };
};

export const updateGraphData = (payload) => {
  const tabClone = getActiveTab();
  return {
    type: UPDATE_TAB_DATA,
    payload: { ...tabClone, ...payload },
  };
};

export const updateSelectedTags = (tagsClone) => {
  const tabClone = getActiveTab();
  return {
    type: UPDATE_TAB_DATA,
    payload: { ...tabClone, selectedTags: tagsClone },
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
  const { activeTab, tabs = {} } = store?.getState()?.successRate;
  const { selectedDropdownFilterOptions } = tabs?.[activeTab];
  const indexToUpdate = selectedDropdownFilterOptions?.findIndex(
    (option) => option?.query === query,
  );
  return {
    type: SET_SELECTED_DROPDOWN_FILTER_OPTIONS,
    payload: { indexToUpdate, option },
  };
};

export const setCardTypeFilter = (value) => {
  return { type: SET_CARD_TYPE_FILTER, payload: value };
};

export const resetSRDashboard = () => {
  return { type: RESET_SR_DASHBOARD };
};

export const setMerchantIDSearch = (query) => {
  return {
    type: SEARCH_MERCHANT_ID,
    payload: query,
  };
};

const getInitialState = () => {
  const state = {
    searchedMerchantId: '',
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

  tabsOrder.forEach(({ tab: tabName, optimizerEnabled }) => {
    state.metrics[tabName] = {
      ...metricsCard,
      name: tabName,
      title: tabsTitleMap[tabName],
      optimizerEnabled,
    };
    state.tabs[tabName] = {
      ...tabMeta,
      name: tabName,
      method: DEFAULT_METHOD[tabName],
      group_by: DEFAULT_GROUP_BY[tabName],
      optimizerEnabled,
    };
  });

  state.merchantErrors = tabsOrder.reduce(
    (acc, item) => ({
      ...acc,
      [item.tab]: {
        failures: {},
        failureReasonType: 'default',
      },
    }),
    {},
  );

  return state;
};

export default (state = getInitialState(), action) => {
  const { type, payload } = action;

  switch (type) {
    case SEARCH_MERCHANT_ID: {
      return merge(state, { searchedMerchantId: payload });
    }

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
      const type = state.merchantErrors[state.activeTab].failureReasonType;
      lodashset(stateClone, 'isLoadingMerchantErrors', false);
      lodashset(stateClone, `merchantErrors.${state.activeTab}.failures.${type}`, payload?.data);

      return stateClone;
    }

    case `${FETCH_MERCHANT_ERRORS}::ERROR`: {
      const stateClone = cloneDeep(state);
      const type = state.merchantErrors[state.activeTab].failureReasonType;
      lodashset(stateClone, 'isLoadingMerchantErrors', false);
      lodashset(stateClone, `merchantErrors.${state.activeTab}.failures.${type}`, payload?.data);
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

    case SET_DEFAULT_INTERVAL: {
      const stateClone = cloneDeep(state);
      Object.keys(state?.tabs)?.forEach((tabName) =>
        lodashset(stateClone, `tabs.${tabName}.selectedInterval`, payload),
      );
      return stateClone;
    }

    case SET_DEFAULT_LAST_UPDATED_AT: {
      const stateClone = cloneDeep(state);

      Object.keys(state?.tabs)?.forEach((tabName) => {
        lodashset(stateClone, `tabs.${tabName}.lastUpdatedAt`, null);
        lodashset(stateClone, `tabs.${tabName}.selectedDropdownFilterOptions`, []);
      });

      return stateClone;
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

    case SET_CARD_TYPE_FILTER: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, `tabs.${state.activeTab}.selectedCardType`, payload);
      return stateClone;
    }

    case SET_FAILURE_REASONS_TYPE: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, `merchantErrors.${state.activeTab}.failureReasonType`, payload);
      return stateClone;
    }

    case RESET_SR_DASHBOARD: {
      const stateClone = getInitialState();
      return stateClone;
    }

    default:
      return state;
  }
};
