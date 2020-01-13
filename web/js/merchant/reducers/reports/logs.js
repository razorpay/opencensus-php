import Log from 'merchant/models/Log';
import {
  listFetchSuccessState,
  getActionName as getFetchActionName,
  makeActionCollectionReducer,
  updateEntityInList,
  fetchAll,
} from 'merchant/reducers/collection';

const PARTNER_LOGS = 'PARTNER_LOGS';
const MERCHANT_LOGS = 'MERCHANT_LOGS';

const getLoadMoreActionName = entity => entity + '_LOAD_MORE';
const getPollLogActionName = entity => entity + '_POLLING';

const partnerLogFetchAction = getFetchActionName(PARTNER_LOGS);
const merchantLogFetchAction = getFetchActionName(MERCHANT_LOGS);

const merchantLogLoadMoreAction = getLoadMoreActionName(MERCHANT_LOGS);

const merchantReportPollLogAction = getPollLogActionName(MERCHANT_LOGS);
const partnerReportPollLogAction = getPollLogActionName(PARTNER_LOGS);

const filterSameObjects = (state, action) => {
  const existingIds = state.items.map(({ id }) => id);
  const newObjects = action.payload.data.items.filter(
    ({ id }) => !existingIds.includes(id)
  );
  return {
    ...state,
    items: [...newObjects, ...state.items],
  };
};

const handleFetchLogsPending = (state, action, initialState) =>
  state.items.length > 0 ? { ...state } : { ...initialState };

const handleFetchLogsSuccess = (state, action) => {
  const firstFetch = state.items.length < 1;

  return firstFetch
    ? listFetchSuccessState(state, action)
    : filterSameObjects(state, action);
};

const handleLoadMoreLogsSuccess = (state, action) => ({
  ...state,
  items: [...state.items, ...action.payload.data.items],
});

export const fetchPartnerReportLogs = params =>
  fetchAll(params, Log, PARTNER_LOGS);
export const partnerLogListReducer = makeActionCollectionReducer(PARTNER_LOGS, {
  [`${partnerLogFetchAction}::PENDING`]: handleFetchLogsPending,
  [`${partnerLogFetchAction}::SUCCESS`]: handleFetchLogsSuccess,
  [`${partnerReportPollLogAction}::SUCCESS`]: updateEntityInList,
});

export const fetchMerchantReportLogs = params =>
  fetchAll(params, Log, MERCHANT_LOGS);
export const merchantLogListReducer = makeActionCollectionReducer(
  MERCHANT_LOGS,
  {
    [`${merchantLogFetchAction}::PENDING`]: handleFetchLogsPending,
    [`${merchantLogFetchAction}::SUCCESS`]: handleFetchLogsSuccess,
    [`${merchantLogLoadMoreAction}::SUCCESS`]: handleLoadMoreLogsSuccess,
    [`${merchantReportPollLogAction}::SUCCESS`]: updateEntityInList,
  }
);

// Actions
export const loadMoreMerchantLogs = params => ({
  type: getLoadMoreActionName(MERCHANT_LOGS),
  payload: new Log().fetchAll(params),
});

const createLog = reportType => {
  const actionName = `${reportType.toUpperCase()}_LOG_CREATE`;
  return payload => ({
    type: actionName,
    payload: new Log({ reportType }).save(payload).then(data => ({
      ...data,
      isNew: true,
    })),
  });
};

export const createPartnerReportLog = createLog('partner');
export const createMerchantReportLog = createLog('merchant');

const pollLog = reportType => {
  const actionName = `${reportType.toUpperCase()}_LOGS_POLLING`;
  return logId => ({
    type: actionName,
    payload: new Log({ reportType }).poll(logId),
  });
};

export const pollPartnerReportLog = pollLog('partner');
export const pollMerchantReportLog = pollLog('merchant');
