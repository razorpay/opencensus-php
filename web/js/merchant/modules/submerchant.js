import Submerchant from 'merchant/models/Submerchant';
import { createLog, getLog, getFile } from 'merchant/modules/reports';

import { merge } from 'rzp/utils/immutable';
import poll from 'rzp/utils/poll/longPoll';

const SUB_MERCHANT_CREATE = 'SUB_MERCHANT_CREATE';
const SUB_MERCHANT_FETCH_DETAILS = 'SUB_MERCHANT_FETCH_DETAILS';
const SUB_MERCHANT_INVITE = 'SUB_MERCHANT_INVITE';
const SUB_MERCHANT_RESEND_INVITE = 'SUB_MERCHANT_RESEND_INVITE';

export const create = payload => {
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

export const invite = (...args) => ({
  type: SUB_MERCHANT_INVITE,
  payload: new Submerchant().invite(...args),
});

export const resendInvite = submerchantId => ({
  type: SUB_MERCHANT_RESEND_INVITE,
  payload: new Submerchant().resendInvite(submerchantId),
});

export const downloadSubmerchants = (isPurePlatform = false) => {
  const startTime = new Date().getTime();

  /**
   * these are hardcoded values
   * reporting service consists of configs table which consist of configurations for different reports
   * download submerchants uses the same service to get all the submerchants of the partner
   * Mentioned config_ids are the id of configs of those respective configurations (which will get us the list of submerchants)
   * For more info see the code of download report
   */
  const config_id = isPurePlatform
    ? 'config_C26ykx5qWFJq0N'
    : 'config_C26zkCd7EcdfTQ';

  // fake params, since reporting service makes it mandatory
  // and they should be one month apart
  // any value won't affect the results
  const end_time = moment().format('X');
  const start_time = moment(end_time, 'X')
    .subtract(1, 'months')
    .format('X');
  return createLog({
    start_time,
    end_time,
    config_id,
    generated_by: 'Azv3fr3tEOayGk',
  }).then(logResponse => {
    if (logResponse.data.id) {
      return poll({
        fetchFunc: () => getLog(logResponse.data.id),
        validator: validatorResp => {
          return (
            validatorResp.error ||
            (validatorResp.data || {}).status !== 'created'
          );
        },
      }).promise.then(pollResponse => {
        console.log({ pollResponse });
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

export default function(state = initialState, action) {
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
