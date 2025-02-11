import ajax, { merchantFetch } from 'merchant/utils/ajax';
import { set } from 'common/utils/immutable';
import poll from 'common/utils/poll/longPoll';

const GENERATE_REPORT = 'GENERATE_REPORT';

const ADD_REPORT = 'ADD_REPORT';
const UPDATE_REPORT = 'UPDATE_REPORT';
const REMOVE_REPORT = 'REMOVE_REPORT';
const ADD_POLL_INSTANCE = 'ADD_POLL_INSTANCE';
const SAVE_REPORT_CONFIGS = 'SAVE_REPORT_CONFIGS';

const downloadReportErrorMsg = {
  error: 'Oops!, Unable to generate report',
};

const emailReportErrorMsg = {
  error: 'Oops!, Unable to email report',
};

const handleError = (e) => {
  if (window.APP_ENV !== 'production') console.error(e);
  return downloadReportErrorMsg;
};

export const createLog = (data, accountId, shouldAppendHeader) => {
  return merchantFetch({
    url: 'reporting/logs',
    method: 'post',
    data,
    ...(!!accountId && { accountId }),
    headers: appendReportTypeHeader(shouldAppendHeader),
  });
};

export const getLog = (logId, accountId, shouldAppendHeader) => {
  return merchantFetch({
    url: `reporting/logs/${logId}`,
    ...(!!accountId && { accountId }),
    headers: appendReportTypeHeader(shouldAppendHeader),
  });
};

export const getFile = (fileId, accountId) => {
  return merchantFetch({
    url: `ufh/file/${fileId}/get-signed-url`,
    ...(!!accountId && { accountId }),
  });
};

const updateLog = (data, accountId, shouldAppendHeader) => {
  return merchantFetch({
    url: `reporting/logs/${data.id}`,
    method: 'patch',
    data: { emails: data.emails },
    ...(!!accountId && { accountId }),
    headers: appendReportTypeHeader(shouldAppendHeader),
  });
};

export const getConfigs = (shouldFetchPartnerConfigs) => {
  return merchantFetch({
    url: 'reporting/configs',
    headers: appendReportTypeHeader(shouldFetchPartnerConfigs),
  });
};

export const saveReportConfigs = (_) => {
  return {
    type: SAVE_REPORT_CONFIGS,
    payload: getConfigs(),
  };
};

export const generateReport = (ajaxParams) => {
  return {
    type: GENERATE_REPORT,
    payload: ajax(ajaxParams),
  };
};

const pollInterval = 2, // poll interval in SECONDS
  timeout = 30 * 60 * 1000; // 30 minutes

export const generateReportV2 = (
  params,
  isMerchantAccount,
  onProgress,
  onPollStart,
  isPartnerReport,
) => {
  const startTime = new Date(),
    accountHeaderVal = !isMerchantAccount && params.generated_by;

  let numCallsMade = 0,
    timeElapsed = 0;

  return createLog(params, accountHeaderVal, isPartnerReport)
    .then((resp) => {
      if (!resp.success || !resp.data || !resp.data.id) {
        onProgress && onProgress(resp.data);
        return downloadReportErrorMsg;
      }

      onProgress && onProgress(resp.data, true);

      const logId = resp.data.id;

      const logPoll = poll({
        fetchFunc: () => getLog(resp.data.id, accountHeaderVal, isPartnerReport),
        validator: (resp) => {
          numCallsMade++;
          timeElapsed = new Date() - startTime;

          /*
           * stop poll when
           * 1) If calls exceeded timeout time
           * 2) If the api throws an error
           * 3) If the log is processed/failed
           */

          return (
            timeElapsed > timeout ||
            resp.error ||
            ['processed', 'failed'].includes(resp.data.status)
          );
        },
        getNextCallWaitime: () => {
          /*
           * Decresing the poll frequency exponentially
           * 2^1 , 2^2, 2^3 .....
           */

          let nextCallWaittime = pollInterval ** numCallsMade * 1000,
            timeToBeElapsed = timeElapsed + nextCallWaittime;

          if (timeToBeElapsed > timeout) {
            nextCallWaittime = timeout - timeElapsed;
          }

          return nextCallWaittime;
        },
      });

      //save log poll instances in the store
      onPollStart(logId, logPoll);

      return logPoll.promise
        .then((resp) => {
          // `resp.data.status` will be `created` in
          // case of timeout
          if (resp.error || resp.data.status === 'failed' || resp.data.status === 'created') {
            onProgress && onProgress({ ...resp.data, status: 'failed' });
            return downloadReportErrorMsg;
          }

          onProgress && onProgress(resp.data);

          const fileId = resp.data.file_id;

          if (resp.data.status === 'processed' && !fileId) {
            onProgress && onProgress(resp.data);
            return {
              error: 'No data found for the given dates',
            };
          }

          return getFile(fileId, accountHeaderVal)
            .then((resp) => {
              if (!resp.success) {
                return downloadReportErrorMsg;
              }

              return {
                url: resp.data.signed_url,
              };
            })
            .catch(handleError);
        })
        .catch(handleError);
    })
    .catch((response) => {
      if (response.errors) {
        const error = response.errors[0];
        return { error };
      }
      return downloadReportErrorMsg;
    });
};

export const emailReportV2 = (params, isMerchantAccount, shouldUpdate = false, isPartnerReport) => {
  const accountHeaderVal = !isMerchantAccount && params.generated_by;
  const reqFunc = shouldUpdate ? updateLog : createLog;

  return reqFunc(params, accountHeaderVal, isPartnerReport)
    .then((resp) => {
      if (!resp.success || !resp.data || !resp.data.id) {
        return emailReportErrorMsg;
      }
      return resp;
    })
    .catch((err) => {
      return emailReportErrorMsg;
    });
};

export const addReportToList = (report) => {
  return {
    type: ADD_REPORT,
    report,
  };
};

export const updateReportInList = (report) => {
  return {
    type: UPDATE_REPORT,
    report,
  };
};

export const removeReportFromList = (reportId) => {
  return {
    type: REMOVE_REPORT,
    reportId,
  };
};

export const addPollInstance = (logId, pollInstance) => {
  return {
    type: ADD_POLL_INSTANCE,
    payload: {
      logId,
      pollInstance,
    },
  };
};

export const areReportsStillDownloading = (reports) => {
  const list = Object.keys(reports);
  let isDownloading = false;

  for (let i = 0, len = list.length; i < len; i++) {
    if (reports[list[i]].status === 'created') {
      isDownloading = true;
      break;
    }
  }

  return isDownloading;
};

let initialState = {
  currentReportList: {},
  pollInstances: {},
  reportConfigs: null,
};

export function reportsReducer(state = initialState, action) {
  let currentReportList = {},
    pollInstances = {};

  currentReportList = { ...state.currentReportList };
  pollInstances = { ...state.pollInstances };

  switch (action.type) {
    case `${ADD_REPORT}`:
      currentReportList[action.report.id] = action.report;
      return set(state, 'currentReportList', currentReportList);

    case `${UPDATE_REPORT}`:
      if (currentReportList[action.report.id]) {
        currentReportList[action.report.id] = action.report;
      }

      return set(state, 'currentReportList', currentReportList);

    case `${SAVE_REPORT_CONFIGS}::SUCCESS`:
      return set(state, 'reportConfigs', action.payload.data.items);

    case `${REMOVE_REPORT}`:
      if (currentReportList[action.reportId]) {
        delete currentReportList[action.reportId];
      }

      return set(state, 'currentReportList', currentReportList);
    case `${ADD_POLL_INSTANCE}`:
      if (!pollInstances[action.payload.logId]) {
        pollInstances[action.payload.logId] = action.payload.pollInstance;
      }

      return set(state, 'pollInstances', pollInstances);
    default:
      return state;
  }
}

function appendReportTypeHeader(isPartnerReport) {
  return {
    ...(isPartnerReport && { 'X-Report-Type': 'partner' }),
  };
}
