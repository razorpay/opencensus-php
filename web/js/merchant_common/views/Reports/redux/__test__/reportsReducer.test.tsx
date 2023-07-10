import {
  reportsInitialStatesForAllDashboards,
  reportsReducer,
  handleOverviewLoading,
  fetchReportsConfigsSuccess,
  fetchReportsConfigsFailed,
  fetchRecentlyUsedConfigsSuccess,
  fetchRecentlyUsedConfigsFailed,
  fetchLogsSuccess,
  handleLogsFilter,
  handleDownloadsPageTrack,
  startLogsPoll,
  stopLogsPoll,
} from 'merchant_common/views/Reports/redux/reducer';
import { mockConfigs } from './fixtures/configs.fixtures';
import { parseReqDataFromConfigs } from 'merchant_common/views/Reports/utils/commonUtils';
import { mockLogs } from './fixtures/logs.fixture';
import { InitialStateType } from 'merchant_common/views/Reports/redux/types';

describe('Core Reports Reducer', () => {
  const dashboardTypes = ['merchant', 'partner', 'linkedAccount'];

  dashboardTypes.forEach((dashboard) => {
    const commonPrefix = `In ${dashboard} dashboard, reports slice`;
    const refInitialState: InitialStateType = reportsInitialStatesForAllDashboards[dashboard];
    const commonPayload = {
      dashboardType: dashboard,
    };
    const initialStateForSlice = undefined;

    test(`${commonPrefix} should return the initial state when passed an empty action`, () => {
      const action = { type: '' };
      const result = reportsReducer(initialStateForSlice, action);
      expect(result).toEqual(reportsInitialStatesForAllDashboards);
      expect(result[dashboard]).toStrictEqual(refInitialState);
    });

    test.each(Object.keys(refInitialState.overview.reportConfigs))(
      `${commonPrefix} should change overview loading state to false when passed state as false`,
      (viewType) => {
        const refViewLoadingState = refInitialState.overview.reportConfigs[viewType].loading;

        expect(refViewLoadingState).toBe(true);

        const action = handleOverviewLoading({
          ...commonPayload,
          key: viewType,
          state: false,
        });
        const result = reportsReducer(initialStateForSlice, action);

        expect(result[dashboard].overview.reportConfigs[viewType].loading).toEqual(false);
      },
    );

    test(`${commonPrefix} should add all the configs from api to allConfigs in state making sure its only having the required data`, () => {
      const action = fetchReportsConfigsSuccess({
        ...commonPayload,
        configs: mockConfigs,
      });

      const result = reportsReducer(initialStateForSlice, action);

      expect(result[dashboard].overview.reportConfigs.allConfigs).toStrictEqual({
        loading: false,
        data: parseReqDataFromConfigs(mockConfigs),
        error: false,
      });
    });

    test(`${commonPrefix} should handle error when no configs was passed`, () => {
      const actionWithError = fetchReportsConfigsFailed({
        ...commonPayload,
        error: 'Err 404',
      });

      const actionWithoutError = fetchReportsConfigsFailed({
        ...commonPayload,
      });

      const resultWithoutErr = reportsReducer(initialStateForSlice, actionWithoutError);
      expect(resultWithoutErr[dashboard].overview.reportConfigs.allConfigs).toStrictEqual({
        loading: false,
        data: [],
        error: 'Something went wrong!',
      });

      const resultWithErr = reportsReducer(initialStateForSlice, actionWithError);
      expect(resultWithErr[dashboard].overview.reportConfigs.allConfigs).toStrictEqual({
        loading: false,
        data: [],
        error: 'Err 404',
      });
    });

    test(`${commonPrefix} should add recently used configs to recentConfigs in state`, () => {
      const action = fetchRecentlyUsedConfigsSuccess({
        ...commonPayload,
        configs: mockConfigs,
      });

      const result = reportsReducer(initialStateForSlice, action);
      expect(result[dashboard].overview.reportConfigs.recentConfigs).toStrictEqual({
        loading: false,
        error: false,
        data: mockConfigs,
      });
    });

    test(`${commonPrefix} should handle error when no recently used configs was passed`, () => {
      const actionWithError = fetchRecentlyUsedConfigsFailed({
        ...commonPayload,
        error: 'Err 404',
      });

      const actionWithoutError = fetchRecentlyUsedConfigsFailed({
        ...commonPayload,
      });

      const resultWithoutErr = reportsReducer(initialStateForSlice, actionWithoutError);
      expect(resultWithoutErr[dashboard].overview.reportConfigs.recentConfigs).toStrictEqual({
        loading: false,
        data: [],
        error: 'Something went wrong!',
      });

      const resultWithErr = reportsReducer(initialStateForSlice, actionWithError);
      expect(resultWithErr[dashboard].overview.reportConfigs.recentConfigs).toStrictEqual({
        loading: false,
        data: [],
        error: 'Err 404',
      });
    });

    test(`${commonPrefix} should add logs to download state`, () => {
      const totalLogsCount = 100;
      const action = fetchLogsSuccess({
        ...commonPayload,
        totalCount: totalLogsCount,
        logs: mockLogs,
      });

      const result = reportsReducer(initialStateForSlice, action);
      expect(result[dashboard].downloads).toEqual({
        ...result[dashboard].downloads,
        logs: mockLogs.reduce((prev, curr) => {
          return {
            ...prev,
            [curr.id]: {
              ...curr,
              polling: false,
            },
          };
        }, {}),
        loading: false,
      });
    });

    test(`${commonPrefix} should change logs filter in downloads state`, () => {
      const egFilter = 'spec-2';
      const action = handleLogsFilter({
        ...commonPayload,
        filter: egFilter,
      });

      const modifiedInitialState = {
        ...reportsInitialStatesForAllDashboards,
        [dashboard]: {
          ...reportsInitialStatesForAllDashboards[dashboard],
          downloads: {
            ...refInitialState.downloads,
            // random page user is on
            pageTrack: 2,
            // total count of logs fetched for prev filter
            totalCount: 200,
            loading: false,
            // assuming poll was not active
            genericPoll: false,
          },
        },
      };

      const result = reportsReducer(modifiedInitialState, action);
      expect(result[dashboard].downloads).toStrictEqual({
        logs: {},
        loading: true,
        genericPoll: true,
        filter: egFilter,
        pageTrack: 1,
        totalCount: null,
      });
    });

    test(`${commonPrefix} should change page track in downloads state`, () => {
      const pgNo = 2;
      const action = handleDownloadsPageTrack({
        ...commonPayload,
        pageNo: 2,
      });

      const modifiedInitialState = {
        ...reportsInitialStatesForAllDashboards,
        [dashboard]: {
          ...reportsInitialStatesForAllDashboards[dashboard],
          downloads: {
            ...refInitialState.downloads,
            // random page user is on
            pageTrack: 1,
            // total count of logs fetched for prev filter
            totalCount: 200,
            loading: false,
            // assuming poll was not active
            genericPoll: false,
            filter: 'dun-2',
          },
        },
      };

      const result = reportsReducer(modifiedInitialState, action);
      expect(result[dashboard].downloads).toStrictEqual({
        logs: {},
        loading: true,
        genericPoll: true,
        filter: 'dun-2',
        pageTrack: pgNo,
        totalCount: 200,
      });
    });

    test(`${commonPrefix} should set the generic poll to true`, () => {
      const action = startLogsPoll({
        ...commonPayload,
        genericPoll: true,
      });

      const modifiedInitialState = {
        ...reportsInitialStatesForAllDashboards,
        [dashboard]: {
          ...reportsInitialStatesForAllDashboards[dashboard],
          downloads: {
            ...refInitialState.downloads,
            genericPoll: false,
          },
        },
      };

      const result = reportsReducer(modifiedInitialState, action);
      expect(result[dashboard].downloads.genericPoll).toBe(true);
    });

    test(`${commonPrefix} should set the generic poll to true`, () => {
      const action = stopLogsPoll({
        ...commonPayload,
        genericPoll: false,
      });

      const modifiedInitialState = {
        ...reportsInitialStatesForAllDashboards,
        [dashboard]: {
          ...reportsInitialStatesForAllDashboards[dashboard],
          downloads: {
            ...refInitialState.downloads,
            genericPoll: true,
          },
        },
      };

      const result = reportsReducer(modifiedInitialState, action);
      expect(result[dashboard].downloads.genericPoll).toBe(false);
    });
  });
});
