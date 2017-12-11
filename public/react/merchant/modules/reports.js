import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';

const GENERATE_REPORT = 'GENERATE_REPORT';

const commonOptions = {
  url: '/user/generic',
  method: 'get',
  appendModeInURL: false,
  appendModeInQueryParam: true,
};

export const getConfigs = () => {
  return ajax({
    ...commonOptions,
    data: {
      route_name: 'reporting_config_list',
    },
  });
};

export const createLog = body => {
  return ajax({
    ...commonOptions,
    method: 'post',
    data: {
      route_name: 'reporting_log_create',
      body,
    },
  });
};

export const getLog = logId => {
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

export const generateReport = ajaxParams => {
  return {
    type: GENERATE_REPORT,
    payload: ajax(ajaxParams),
  };
};
