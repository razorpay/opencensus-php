import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';
import poll from 'rzp/utils/poll/longPoll';
import { merchantFetch } from 'rzp/utils/ajax';

const GENERATE_REPORT = 'GENERATE_REPORT';

const reportErrorMsg = {
  error: 'Oops!, Unable to generate report',
};

const handleError = e => {
  console.error(e);
  return reportErrorMsg;
};

const createLog = data => {
  return merchantFetch({
    url: 'reporting/logs',
    method: 'post',
    data,
  });
};

const getLog = logId => merchantFetch(`reporting/logs/${logId}`);
const getFile = fileId => merchantFetch(`ufh/file/${fileId}/get-signed-url`)
export const getConfigs = _ => merchantFetch('reporting/configs')

export const generateReport = ajaxParams => {
  return {
    type: GENERATE_REPORT,
    payload: ajax(ajaxParams),
  };
};

const pollInterval = 2, // poll interval in SECONDS
  timeout = 30 * 60 * 1000; // 30 minutes

export const generateReportV2 = (params, isMerchantAccount) => {
  const startTime = new Date();

  let numCallsMade = 0,
    timeElapsed = 0;

  if (!isMerchantAccount) {
  
    params.accountId = params.generated_by;
  }

  return createLog(params)
    .then(resp => {
      if (!resp.success) {
        return reportErrorMsg;
      }

      const logId = resp.data.id;

      const logPoll = poll({
        fetchFunc: () => getLog(resp.data.id),
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
            return reportErrorMsg;
          }

          const fileId = resp.data.file_id;

          if (!fileId) {
            return {
              error: 'No data found for the given dates',
            };
          }

          return getFile(fileId)
            .then(resp => {
              if (!resp.success) {
                return reportErrorMsg;
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
