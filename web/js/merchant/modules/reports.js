import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';
import poll from 'rzp/utils/poll/longPoll';
import { merchantFetch } from 'rzp/utils/ajax';
import { trackReportGenericActions } from 'merchant/containers/Reports/ReportsNew/ga';

const GENERATE_REPORT = 'GENERATE_REPORT';

const ADD_REPORT = 'ADD_REPORT';
const UPDATE_REPORT = 'UPDATE_REPORT';
const REMOVE_REPORT = 'REMOVE_REPORT';

const downloadReportErrorMsg = {
  error: 'Oops!, Unable to generate report',
};

const emailReportErrorMsg = {
  error: 'Oops!, Unable to email report',
};

const handleError = e => {
  console.error(e);
  return downloadReportErrorMsg;
};

const createLog = (data, accountId) => {
  return merchantFetch({
    url: 'reporting/logs',
    method: 'post',
    data,
    ...(!!accountId && { accountId }),
  });
};

const getLog = (logId, accountId) => {
  return merchantFetch({
    url: `reporting/logs/${logId}`,
    ...(!!accountId && { accountId }),
  });
};

const getFile = (fileId, accountId) => {
  return merchantFetch({
    url: `ufh/file/${fileId}/get-signed-url`,
    ...(!!accountId && { accountId }),
  });
};

const updateLog = (data, accountId) => {
  return merchantFetch({
    url: `reporting/logs/${data.id}`,
    method: 'patch',
    data: { emails: data.emails },
    ...(!!accountId && { accountId }),
  });
};

export const getConfigs = () => {
  return merchantFetch({
    url: 'reporting/configs',
  });
};

export const generateReport = ajaxParams => {
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
  updateStore,
  saveLongPollInstances
) => {
  const startTime = new Date(),
    accountHeaderVal = !isMerchantAccount && params.generated_by;

  let numCallsMade = 0,
    timeElapsed = 0;

  return createLog(params, accountHeaderVal)
    .then(resp => {
      if (!resp.success || !resp.data || !resp.data.id) {
        updateStore(resp.data);
        return downloadReportErrorMsg;
      }

      updateStore(resp.data, true);

      const logId = resp.data.id;

      const logPoll = poll({
        fetchFunc: () => getLog(resp.data.id, accountHeaderVal),
        validator: resp => {
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
            resp.data.status !== 'created'
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

      saveLongPollInstances(logId, logPoll);

      return logPoll.promise
        .then(resp => {
          // `resp.data.status` will be `created` in
          // case of timeout
          if (
            resp.error ||
            resp.data.status === 'failed' ||
            resp.data.status === 'created'
          ) {
            updateStore({ ...resp.data, status: 'failed' });
            return downloadReportErrorMsg;
          }

          updateStore(resp.data);

          const fileId = resp.data.file_id;

          if (!fileId) {
            updateStore(resp.data);
            return {
              error: 'No data found for the given dates',
            };
          }

          return getFile(fileId, accountHeaderVal)
            .then(resp => {
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
    .catch(handleError);
};

export const emailReportV2 = (
  params,
  isMerchantAccount,
  shouldUpdate = false
) => {
  const accountHeaderVal = !isMerchantAccount && params.generated_by;
  const reqFunc = shouldUpdate ? updateLog : createLog;

  return reqFunc(params, accountHeaderVal)
    .then(resp => {
      if (!resp.success || !resp.data || !resp.data.id) {
        return emailReportErrorMsg;
      }
      return resp;
    })
    .catch(err => {
      return emailReportErrorMsg;
    });
};

export const addReportToList = report => {
  return {
    type: ADD_REPORT,
    report,
  };
};

export const updateReportInList = report => {
  return {
    type: UPDATE_REPORT,
    report,
  };
};

export const removeReportFromList = reportId => {
  return {
    type: REMOVE_REPORT,
    reportId,
  };
};

export const areReportsStillDownloading = reports => {
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
};

export function reportsReducer(state = initialState, action) {
  let currentReportList = {},
    reportsDownloadStatus = {};

  currentReportList = { ...state.currentReportList };
  reportsDownloadStatus = { ...state.reportsDownloadStatus };

  switch (action.type) {
    case `${ADD_REPORT}`:
      //attach unload event at the first report download
      if (
        Object.keys(currentReportList).length === 0 &&
        !window.onbeforeunload
      ) {
        window.onbeforeunload = alertBeforeClose;
      }

      currentReportList[action.report.id] = action.report;
      return set(state, 'currentReportList', currentReportList);

    case `${UPDATE_REPORT}`:
      if (currentReportList[action.report.id]) {
        currentReportList[action.report.id] = action.report;
      }

      return set(state, 'currentReportList', currentReportList);

    case `${REMOVE_REPORT}`:
      if (currentReportList[action.reportId]) {
        delete currentReportList[action.reportId];
      }

      //de-attach unload event at the first report download
      if (Object.keys(currentReportList).length === 0) {
        window.onbeforeunload = null;
      }
      return set(state, 'currentReportList', currentReportList);
    default:
      return state;
  }
}

const alertBeforeClose = () => {
  trackReportGenericActions('Attempt to Close Browser Window');

  return 'Some reports are currently being downloaded. Are you sure you want to close the app right now?';
};
