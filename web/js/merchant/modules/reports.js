import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';
import poll from 'rzp/utils/poll/longPoll';

const GENERATE_REPORT = 'GENERATE_REPORT';

const reportErrorMsg = {
  error: 'Oops!, Unable to generate report',
};

const handleError = e => {
  console.error(e);
  return reportErrorMsg;
};

const commonOptions = {
  url: '/user/generic',
  method: 'get',
  appendModeInURL: false,
  appendModeInQueryParam: true,
};

const createLog = body => {
  return ajax({
    ...commonOptions,
    method: 'post',
    data: {
      route_name: 'reporting_log_create',
      body,
    },
  });
};

const getLog = logId => {
  return ajax({
    ...commonOptions,
    data: {
      route_name: 'reporting_log_get',
      url_params: JSON.stringify({
        '{id}': logId,
      }),
    },
  });
};

const getFile = fileId => {
  return ajax({
    ...commonOptions,
    data: {
      route_name: 'ufh_get_file_signed_url',
      url_params: JSON.stringify({
        '{fileId}': fileId,
      }),
    },
  });
};

export const getConfigs = () => {
  return ajax({
    ...commonOptions,
    data: {
      route_name: 'reporting_config_list',
    },
  });
};

export const generateReport = ajaxParams => {
  return {
    type: GENERATE_REPORT,
    payload: ajax(ajaxParams),
  };
};

export const generateReportV2 = params => {
  const startTime = new Date();

  return createLog(params)
    .then(resp => {
      if (!resp.success) {
        return reportErrorMsg;
      }

      const logId = resp.data.id;

      const logPoll = poll({
        fetchFunc: () => getLog(resp.data.id),
        validator: resp => {
          /* 
           * stop poll when
           * 1) It takes more than 3 minutes to process log
           * 2) If the api throws an error
           * 3) If the log is processed/failed
           */
          return (
            new Date() - startTime > 3 * 60 * 1000 ||
            resp.error ||
            resp.data.status !== 'created'
          );
        },
        minWaitTime: 2000,
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
