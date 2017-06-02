import Webhook from 'merchant/models/Webhook';
import { set, merge, unshift } from 'rzp/utils/immutable';

const WEBHOOKS_FETCH = 'WEBHOOKS_FETCH';
const WEBHOOK_CREATE = 'WEBHOOK_CREATE';
const WEBHOOK_EDIT = 'WEBHOOK_EDIT';

export const fetchWebhooks = params => {
  return dispatch => {
    let webhook = new Webhook(params);
    return dispatch({
      type: WEBHOOKS_FETCH,
      payload: webhook.fetchAll(params),
    });
  };
};

export const saveWebhook = params => {
  return dispatch => {
    let webhook = new Webhook(params);
    return dispatch({
      type: webhook.isNew ? WEBHOOK_CREATE : WEBHOOK_EDIT,
      payload: webhook.save(),
    });
  };
};

let initialState = {
  loading: true,
  webhooks: [],
  count: 0,
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${WEBHOOKS_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${WEBHOOKS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        webhooks: action.payload.data.items,
        count: action.payload.data.count,
        error: null,
      });

    case `${WEBHOOKS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
      });

    case `${WEBHOOK_CREATE}::SUCCESS`:
      return set(state, 'webhooks', unshift(state.webhooks, action.payload));

    case `${WEBHOOK_EDIT}::SUCCESS`:
      let webhookIndex = state.webhooks.findIndex(
        webhook => webhook.id === action.payload.id
      );
      return set(state, `webhooks.${webhookIndex}`, action.payload);

    default:
      return state;
  }
}
