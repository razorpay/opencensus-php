import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';
import poll from 'rzp/utils/poll/longPoll';

const GENERATE_REPORT = 'GENERATE_REPORT';

const reportErrorMsg = {
  error: 'Oops!, Unable to generate report!',
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
  return createLog(params)
    .then(resp => {
      if (!resp.success) {
        return reportErrorMsg;
      }

      const logId = resp.data.id;

      const logPoll = poll({
        fetchFunc: () => getLog(resp.data.id),
        validator: resp => {
          return resp.error || resp.data.status !== 'created';
        },
        minWaitTime: 2000,
      });

      return logPoll.promise
        .then(resp => {
          if (resp.error || resp.data.status === 'failed') {
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
            .catch(() => {
              return reportErrorMsg;
            });
        })
        .catch(() => {
          return reportErrorMsg;
        });
    })
    .catch(() => {
      return reportErrorMsg;
    });
};
