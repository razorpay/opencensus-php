import poll from 'rzp/utils/poll/longPoll';
import { adminFetch, adminPost } from 'common/fetch';

const customConfigsMap = {
  monthlyInvoice: 'Monthly Invoice',
  dsp_report: 'DSP Transaction Report',
  broking: 'Broking Report',
  rpp_report: 'e-Mitra Report',
};

export const getCustomConfig = key => {
  if (!customConfigsMap[key]) {
    return null;
  }

  return {
    label: customConfigsMap[key],
    type: 'custom',
    id: key,
  };
};

/* Generate reports functions */
const reportErrorMsg = {
  error: 'Oops!, Unable to generate report',
};

const handleError = e => {
  console.error(e);
  return reportErrorMsg;
};

const createLog = (data, merchantId) => {
  return adminPost({
    url: `live_${merchantId}/reporting/logs`,
    data,
  });
};

const getLog = (logId, merchantId) => {
  return adminFetch({
    url: `live_${merchantId}/reporting/logs/${logId}`,
  });
};

const getFile = (fileId, merchantId) => {
  return adminFetch({
    url: `live_${merchantId}/ufh/file/${fileId}/get-signed-url`,
  });
};

const pollInterval = 2, // poll interval in SECONDS
  timeout = 30 * 60 * 1000; // 30 minutes

export const generateReportV2 = params => {
  const startTime = new Date();
  const merchantId = params.generated_by;

  let numCallsMade = 0,
    timeElapsed = 0;

  return createLog(params, merchantId)
    .then(resp => {
      if (!resp || !resp.id) {
        return reportErrorMsg;
      }

      const logId = resp.id;

      const logPoll = poll({
        fetchFunc: () => getLog(resp.id, merchantId),
        validator: resp => {
          numCallsMade++;
          timeElapsed = new Date() - startTime;

          /*
           * stop poll when
           * 1) If calls exceeded timeout time
           * 2) If the api throws an error
           * 3) If the log is processed/failed
           */

          return timeElapsed > timeout || resp.status !== 'created';
        },
        getNextCallWaitime: () => {
          /*
           * Decreasing the poll frequency exponentially
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
          // `resp.status` will be `created` in
          // case of timeout
          if (resp.status === 'failed' || resp.status === 'created') {
            return reportErrorMsg;
          }

          const fileId = resp.file_id;

          if (!fileId) {
            return {
              error: 'No data found for the given dates',
            };
          }

          return getFile(fileId, merchantId)
            .then(resp => {
              if (!resp) {
                return reportErrorMsg;
              }

              return {
                url: resp.signed_url,
              };
            })
            .catch(handleError);
        })
        .catch(handleError);
    })
    .catch(handleError);
};
