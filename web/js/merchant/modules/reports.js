import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';
import poll from 'rzp/utils/poll/longPoll';
import { merchantFetch } from 'rzp/utils/ajax';

const GENERATE_REPORT = 'GENERATE_REPORT';

const ADD_REPORT = 'ADD_REPORT';
const REMOVE_REPORT = 'REMOVE_REPORT';
const UPDATE_REPORT = 'UPDATE_REPORT';

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

//TODO: not working!
const updateLog = (data, accountId) => {
  const logId = data.id;

  delete data.id;

  return merchantFetch({
    url: `reporting/logs/${logId}`,
    method: 'patch',
    data,
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

export const generateReportV2 = (params, isMerchantAccount, addReport) => {
  const startTime = new Date(),
    accountHeaderVal = !isMerchantAccount && params.generated_by;

  let numCallsMade = 0,
    timeElapsed = 0;

  return createLog(params, accountHeaderVal)
    .then(resp => {
      if (!resp.success || !resp.data || !resp.data.id) {
        return downloadReportErrorMsg;
      }

      addReport(resp.data);

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

      return logPoll.promise
        .then(resp => {
          // `resp.data.status` will be `created` in
          // case of timeout
          if (
            resp.error ||
            resp.data.status === 'failed' ||
            resp.data.status === 'created'
          ) {
            return downloadReportErrorMsg;
          }

          const fileId = resp.data.file_id;

          if (!fileId) {
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

export const removeReportFromList = reportId => {
  return {
    type: REMOVE_REPORT,
    reportId,
  };
};

export const updateReportInList = report => {
  return {
    type: UPDATE_REPORT,
    report,
  };
};

let initialState = {
  currentReportList: {},
};

export function reportsReducer(state = initialState, action) {
  let currentReportList = {};
  currentReportList = { ...state.currentReportList };

  switch (action.type) {
    case `${ADD_REPORT}`:
      //attach unload event at the first report download
      if (
        Object.keys(currentReportList).length === 0 &&
        !window.onbeforeunload
      ) {
        window.onbeforeunload = alertBeforeClose;
      }

      currentReportList[action.report.config_id] = action.report;
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

    // add emails to report config
    case `${UPDATE_REPORT}`:
      if (currentReportList[action.report.config_id]) {
        currentReportList[action.report.config_id] = action.report;
      }

      return set(state, 'currentReportList', currentReportList);
    default:
      return state;
  }
}

const alertBeforeClose = () => {
  return 'Some reports are currently being downloaded. Are you sure that you want to close the app right now?';
};
