import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { ReportsInitialStateType } from './types';
import { createSlice } from '@reduxjs/toolkit';
import { downloadsFilterDropdown } from 'merchant_common/views/Reports/features/Downloads/constants/dropdownOptions';
import { uniqBy } from 'lodash';
import { parseReqDataFromConfigs } from 'merchant_common/views/Reports/utils/commonUtils';

export const reportsInitialState = {
  overview: {
    reportConfigs: {
      allConfigs: {
        loading: true,
        data: [],
        error: false,
      },
      recentConfigs: {
        loading: true,
        data: [],
        error: false,
      },
    },
  },
  downloads: {
    loading: true,
    filter: downloadsFilterDropdown[0].value,
    pageTrack: 1,
    logs: {},
    totalCount: null,
    genericPoll: true,
  },
};

export const reportsInitialStatesForAllDashboards: ReportsInitialStateType = {
  partner: reportsInitialState,
  merchant: reportsInitialState,
  linkedAccount: reportsInitialState,
};

const reportsSlice = createSlice({
  initialState: reportsInitialStatesForAllDashboards,
  name: 'reportsCore',
  reducers: {
    // Overview
    handleOverviewLoading: (state, action) => {
      state[action.payload.dashboardType].overview.reportConfigs[action.payload.key].loading =
        action.payload.state;
    },
    fetchReportsConfigsSuccess: (state, action) => {
      const parsedData: BaseConfigType[] = parseReqDataFromConfigs(action.payload.configs);
      // wrapped just for testing
      state[action.payload.dashboardType].overview.reportConfigs.allConfigs.data = uniqBy(
        parsedData,
        (e) => e.name,
      );
      state[action.payload.dashboardType].overview.reportConfigs.allConfigs.loading = false;
    },
    fetchReportsConfigsFailed: (state, action) => {
      state[action.payload.dashboardType].overview.reportConfigs.allConfigs.error =
        action?.payload?.error ?? 'Something went wrong!';
      state[action.payload.dashboardType].overview.reportConfigs.allConfigs.loading = false;
    },
    fetchRecentlyUsedConfigsSuccess: (state, action) => {
      state[action.payload.dashboardType].overview.reportConfigs.recentConfigs.data =
        action.payload.configs;
      state[action.payload.dashboardType].overview.reportConfigs.recentConfigs.loading = false;
    },
    fetchRecentlyUsedConfigsFailed: (state, action) => {
      state[action.payload.dashboardType].overview.reportConfigs.recentConfigs.error =
        action?.payload?.error ?? 'Something went wrong!';
      state[action.payload.dashboardType].overview.reportConfigs.recentConfigs.loading = false;
    },

    // Downloads
    fetchLogsSuccess: (state, action) => {
      state[action.payload.dashboardType].downloads.totalCount = action.payload.totalCount;
      state[action.payload.dashboardType].downloads.logs = action.payload.logs.reduce(
        (prev, curr) => {
          return {
            ...prev,
            [curr.id]: {
              ...curr,
              polling: false,
            },
          };
        },
        {},
      );
      state[action.payload.dashboardType].downloads.loading = false;
    },

    handleLogsFilter: (state, action) => {
      // enable poll on filter change
      state[action.payload.dashboardType].downloads.logs = {};
      state[action.payload.dashboardType].downloads.loading = true;
      state[action.payload.dashboardType].downloads.genericPoll = true;
      state[action.payload.dashboardType].downloads.filter = action.payload.filter;
      state[action.payload.dashboardType].downloads.pageTrack = 1;
      state[action.payload.dashboardType].downloads.totalCount = null;
    },
    handleDownloadsPageTrack: (state, action) => {
      // enable poll on page change
      state[action.payload.dashboardType].downloads.logs = {};
      state[action.payload.dashboardType].downloads.loading = true;
      state[action.payload.dashboardType].downloads.genericPoll = true;
      state[action.payload.dashboardType].downloads.pageTrack = action.payload.pageNo;
    },

    // start generic download logs poll
    startLogsPoll: (state, action) => {
      state[action.payload.dashboardType].downloads.genericPoll = true;
    },
    // stop generic download logs poll
    stopLogsPoll: (state, action) => {
      state[action.payload.dashboardType].downloads.genericPoll = false;
    },
  },
});

export const {
  fetchReportsConfigsFailed,
  fetchReportsConfigsSuccess,
  fetchRecentlyUsedConfigsSuccess,
  handleOverviewLoading,
  fetchRecentlyUsedConfigsFailed,
  fetchLogsSuccess,
  handleLogsFilter,
  handleDownloadsPageTrack,
  startLogsPoll,
  stopLogsPoll,
} = reportsSlice.actions;

export const reportsReducer = reportsSlice.reducer;
