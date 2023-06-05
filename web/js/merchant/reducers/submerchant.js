import Submerchant from 'merchant/models/Submerchant';
import { createLog, getLog, getFile } from 'merchant/reducers/reports';
import moment from 'moment';

import { merge } from 'common/utils/immutable';
import poll from 'common/utils/poll/longPoll';

const SUB_MERCHANT_CREATE = 'SUB_MERCHANT_CREATE';
const SUB_MERCHANT_FETCH_DETAILS = 'SUB_MERCHANT_FETCH_DETAILS';
const SUB_MERCHANT_INVITE = 'SUB_MERCHANT_INVITE';
const SUB_MERCHANT_RESEND_INVITE = 'SUB_MERCHANT_RESEND_INVITE';

export const create = (payload) => {
  return {
    type: SUB_MERCHANT_CREATE,
    payload: new Submerchant().create(payload),
  };
};

export const fetchSubmerchant = (submerchantId, application_id) => {
  return {
    type: SUB_MERCHANT_FETCH_DETAILS,
    payload: new Submerchant().fetch(submerchantId, { application_id }),
  };
};

export const fetchSubmerchantWithProduct = (submerchantId, application_id, product) => {
  return {
    type: SUB_MERCHANT_FETCH_DETAILS,
    payload: new Submerchant().fetch(submerchantId, { application_id, product }),
  };
};

export const invite = (...args) => ({
  type: SUB_MERCHANT_INVITE,
  payload: new Submerchant().invite(...args),
});

export const resendInvite = (submerchantId, product) => ({
  type: SUB_MERCHANT_RESEND_INVITE,
  payload: new Submerchant().resendInvite(submerchantId, product),
});

const TIMEOUT = 20 * 60 * 1000; // 20 minutes;
export const downloadSubmerchants = (isPurePlatform = false, generated_by) => {
  const errorObject = {
    error: true,
  };
  const startTime = new Date();

  /**
   * these are hardcoded values
   * reporting service consists of configs table which consist of configurations for different reports
   * download submerchants uses the same service to get all the submerchants of the partner
   * Mentioned config_ids are the id of configs of those respective configurations (which will get us the list of submerchants)
   * For more info see the code of download report
   *
   * Config Ids updated- https://razorpay.slack.com/archives/C3Y0UA0CB/p1652774629607189?thread_ts=1650438618.881999&cid=C3Y0UA0CB
   */
  const config_id = isPurePlatform ? 'config_JWDlBNXBHpftdA' : 'config_JVte5nwrztAora';

  // fake params, since reporting service makes it mandatory
  // and they should be one month apart
  // any value won't affect the results
  const end_time = moment().format('X');
  const start_time = moment(end_time, 'X').subtract(1, 'months').format('X');

  return createLog({
    start_time,
    end_time,
    config_id,
    generated_by,
    // eslint-disable-next-line consistent-return
  }).then((logResponse) => {
    if (logResponse.data.id) {
      return poll({
        fetchFunc: () => getLog(logResponse.data.id),

        validator: (validatorResp) => {
          const timeElapsed = new Date() - startTime;
          return (
            validatorResp.error ||
            timeElapsed > TIMEOUT ||
            ['processed', 'failed'].includes((validatorResp.data || {}).status)
          );
        },
      }).promise.then((pollResponse) => {
        const { error, data } = pollResponse;

        if (error || ['created', 'processing', 'failed'].includes((data || {}).status)) {
          return errorObject;
        }

        const fileId = pollResponse.data.file_id;
        return getFile(fileId);
      });
    }
  });
};

const initialState = {
  loading: true,
  item: {},
  error: null,
};

export default function reducer(state = initialState, action) {
  switch (action.type) {
    case `${SUB_MERCHANT_FETCH_DETAILS}::PENDING`:
      return merge(state, {
        loading: true,
        item: {},
        error: null,
      });

    case `${SUB_MERCHANT_FETCH_DETAILS}::SUCCESS`:
      return merge(state, {
        item: action.payload,
        loading: false,
        error: null,
      });

    case `${SUB_MERCHANT_FETCH_DETAILS}::ERROR`:
      return merge(state, {
        loading: false,
        item: {},
        error: action.payload.errors,
      });
    case `${SUB_MERCHANT_INVITE}::SUCCESS`:
      return merge(state, {
        item: {
          ...state.item,
          user: action.payload,
        },
      });

    default:
      return state;
  }
}
