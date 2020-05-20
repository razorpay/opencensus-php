import Log from 'merchant/models/Reports/Log';
import {
  listFetchSuccessState,
  getActionName as getFetchActionName,
  makeActionCollectionReducer,
  updateEntityInList,
  appendEntityToList,
  fetchAll,
} from 'merchant/reducers/collection';

const PARTNER_LOGS = 'PARTNER_LOGS';
const MERCHANT_LOGS = 'MERCHANT_LOGS';

const getPollLogActionName = entity => entity + '_POLLING';
const getLogCreateActionName = entity =>
  `${entity.substring(0, entity.length - 1)}_CREATE`;

const partnerLogFetchAction = getFetchActionName(PARTNER_LOGS);
const merchantLogFetchAction = getFetchActionName(MERCHANT_LOGS);

const merchantReportPollLogAction = getPollLogActionName(MERCHANT_LOGS);
const partnerReportPollLogAction = getPollLogActionName(PARTNER_LOGS);

const merchantReportLogCreateAction = getLogCreateActionName(MERCHANT_LOGS);
const partnerReportLogCreateAction = getLogCreateActionName(PARTNER_LOGS);

const handleFetchLogsPending = state => ({
  error: null,
  pending: true,
  items: state.items,
});

const handleFetchLogsSuccess = (state, action) => ({
  ...state,
  pending: false,
  items: [...state.items, ...action.payload.data.items],
  allFetched: action.payload.data.count < 5,
});

const appendEntityIfNotDuplicated = (state, action) =>
  action.payload.is_already_present
    ? { ...state }
    : appendEntityToList(state, action);

export const fetchPartnerReportLogs = params =>
  fetchAll(params, new Log({ reportType: 'partner' }), PARTNER_LOGS);

export const partnerLogListReducer = makeActionCollectionReducer(PARTNER_LOGS, {
  [`${partnerLogFetchAction}::PENDING`]: handleFetchLogsPending,
  [`${partnerLogFetchAction}::SUCCESS`]: handleFetchLogsSuccess,
  [`${partnerReportPollLogAction}::SUCCESS`]: updateEntityInList,
  [`${partnerReportLogCreateAction}::SUCCESS`]: appendEntityIfNotDuplicated,
});

export const fetchMerchantReportLogs = params =>
  fetchAll(params, new Log({ reportType: 'merchant' }), MERCHANT_LOGS);

export const merchantLogListReducer = makeActionCollectionReducer(
  MERCHANT_LOGS,
  {
    [`${merchantLogFetchAction}::PENDING`]: handleFetchLogsPending,
    [`${merchantLogFetchAction}::SUCCESS`]: handleFetchLogsSuccess,
    [`${merchantReportPollLogAction}::SUCCESS`]: updateEntityInList,
    [`${merchantReportLogCreateAction}::SUCCESS`]: appendEntityIfNotDuplicated,
  }
);

const createLog = reportType => {
  const actionName = `${reportType.toUpperCase()}_LOG_CREATE`;
  return (payload, accountId) => ({
    type: actionName,
    payload: new Log({ reportType, accountId }).save(payload).then(data => ({
      ...data,
      isNew: true,
    })),
  });
};

export const createPartnerReportLog = createLog('partner');
export const createMerchantReportLog = createLog('merchant');

const pollLog = reportType => {
  const actionName = `${reportType.toUpperCase()}_LOGS_POLLING`;
  return (logId, accountId) => ({
    type: actionName,
    payload: new Log({ reportType, accountId }).poll(logId),
  });
};

export const pollPartnerReportLog = pollLog('partner');
export const pollMerchantReportLog = pollLog('merchant');
