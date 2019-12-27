import Log from 'merchant/models/Log';
import {
  listFetchSuccessState,
  getActionName as getFetchActionName,
  makeActionCollectionReducer,
  fetchAll,
  getActionName,
} from 'merchant/reducers/collection';

const PARTNER_LOGS = 'PARTNER_LOGS';
const MERCHANT_LOGS = 'MERCHANT_LOGS';

const partnerLogFetchAction = getFetchActionName(PARTNER_LOGS);
const merchantLogFetchAction = getFetchActionName(MERCHANT_LOGS);

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
  console.log({ firstFetch });
  return firstFetch
    ? listFetchSuccessState(state, action)
    : filterSameObjects(state, action);
};

export const fetchPartnerReportLogs = params =>
  fetchAll(params, Log, PARTNER_LOGS);
export const partnerLogListReducer = makeActionCollectionReducer(PARTNER_LOGS, {
  [`${partnerLogFetchAction}::PENDING`]: handleFetchLogsPending,
  [`${partnerLogFetchAction}::SUCCESS`]: handleFetchLogsSuccess,
});

export const fetchMerchantReportLogs = params =>
  fetchAll(params, Log, MERCHANT_LOGS);
export const merchantLogListReducer = makeActionCollectionReducer(
  MERCHANT_LOGS,
  {
    [`${merchantLogFetchAction}::PENDING`]: handleFetchLogsPending,
    [`${merchantLogFetchAction}::SUCCESS`]: handleFetchLogsSuccess,
  }
);

const createLog = reportType => {
  const actionName = `${reportType.toUpperCase()}_LOG_CREATE`;
  return payload => ({
    type: actionName,
    payload: new Log({ reportType }).save(payload),
  });
};

export const createPartnerReportLog = createLog('partner');
export const createMerchantReportLog = createLog('merchant');
