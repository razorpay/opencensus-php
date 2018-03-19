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
    type: key,
    id: 'custom',
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
    url: `live/${merchantId}reporting/logs`,
    data,
  });
};

const getLog = (logId, merchantId) => {
  return adminFetch({
    url: `live/${merchantId}/reporting/logs/${logId}`,
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
      if (!resp.success || !resp.data || !resp.data.id) {
        return reportErrorMsg;
      }

      const logId = resp.data.id;

      const logPoll = poll({
        fetchFunc: () => getLog(resp.data.id, merchantId),
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

          return getFile(fileId, merchantId)
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
