import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';

const GENERATE_REPORT = 'GENERATE_REPORT';

export const generateReport = ajaxParams => {
  return dispatch => {
    return dispatch({
      type: GENERATE_REPORT,
      payload: ajax(ajaxParams),
    });
  };
};
