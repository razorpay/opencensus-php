import Webhook from 'merchant/models/Webhook';
import { set, merge, unshift } from 'common/utils/immutable';

const WEBHOOKS_FETCH_ALL = 'WEBHOOKS_FETCH_ALL';
const WEBHOOK_FETCH = 'WEBHOOK_FETCH';
const WEBHOOK_CREATE = 'WEBHOOK_CREATE';
const WEBHOOK_EDIT = 'WEBHOOK_EDIT';
const WEBHOOK_DELETE = 'WEBHOOK_DELETE';
const WEBHOOK_STATS_FETCH = 'WEBHOOK_STATS_FETCH';

export const fetchWebhooks = (params) => {
  const webhook = new Webhook(params);

  return {
    type: WEBHOOKS_FETCH_ALL,
    payload: webhook.fetchAll(params),
  };
};

export const fetchWebhook = (params) => {
  const nextParams = {
    webhook_id: params.id,
  };
  const webhook = new Webhook(nextParams);

  return {
    type: WEBHOOK_FETCH,
    payload: webhook.fetch(nextParams.webhook_id, nextParams),
  };
};

export const saveWebhook = (params) => {
  const webhook = new Webhook(params);

  return {
    type: webhook.isNew ? WEBHOOK_CREATE : WEBHOOK_EDIT,
    payload: webhook.save(),
  };
};

export const deleteWebhook = (params) => {
  const webhook = new Webhook(params);
  return {
    type: WEBHOOK_DELETE,
    payload: webhook.delete(),
  };
};

export const fetchStats = (webhookId, params) => {
  return {
    type: WEBHOOK_STATS_FETCH,
    payload: new Webhook({ id: webhookId }).getAnalytics(params),
  };
};

const initialState = {
  loadingAllWebhooks: true,
  loadingWebhook: false,
  webhooks: [],
  count: 0,
  stats: {
    loading: true,
    data: {},
    error: false,
  },
  error: null,
};

export default function webhookReducer(state = initialState, action) {
  switch (action.type) {
    case `${WEBHOOKS_FETCH_ALL}::PENDING`:
      return merge(state, { loadingAllWebhooks: true, webhooks: [], error: null, count: 0 });

    case `${WEBHOOK_FETCH}::PENDING`:
      return set(state, 'loadingWebhook', true);

    case `${WEBHOOK_FETCH}::SUCCESS`: {
      const webhookPayload = action.payload;
      let webhookUpdated = false;
      let newWebhooksState = [];
      if (state.webhooks.length) {
        newWebhooksState = state.webhooks.map((webhook) => {
          if (webhook.id === webhookPayload.id) {
            webhookUpdated = true;
            return {
              ...webhook,
              ...webhookPayload,
            };
          } else {
            return webhook;
          }
        });
      }
      if (!webhookUpdated) {
        newWebhooksState = [...state.webhooks, webhookPayload];
      }

      return merge(state, {
        loadingWebhook: false,
        webhooks: newWebhooksState,
        count: newWebhooksState.length,
        error: null,
      });
    }

    case `${WEBHOOKS_FETCH_ALL}::SUCCESS`:
      return merge(state, {
        loadingAllWebhooks: false,
        webhooks: action.payload.data.items,
        count: action.payload.data.items.length,
        error: null,
      });

    case `${WEBHOOKS_FETCH_ALL}::ERROR`:
      return merge(state, {
        loadingAllWebhooks: false,
        error: action.payload.errors,
      });

    case `${WEBHOOK_CREATE}::SUCCESS`:
      return set(state, 'webhooks', unshift(state.webhooks, action.payload));

    case `${WEBHOOK_EDIT}::SUCCESS`: {
      const webhookIndex = state.webhooks.findIndex((webhook) => webhook.id === action.payload.id);
      return set(state, `webhooks.${webhookIndex}`, action.payload);
    }

    case `${WEBHOOK_EDIT}::ERROR`:
      return set(state, 'error', action.payload.errors);

    case `${WEBHOOK_STATS_FETCH}::PENDING`:
      return set(state, 'stats', { ...state.stats, loading: true });

    case `${WEBHOOK_STATS_FETCH}::SUCCESS`:
      return set(state, 'stats', {
        ...state.stats,
        loading: false,
        data: action.payload.data,
      });

    case `${WEBHOOK_STATS_FETCH}::ERROR`:
      return set(state, 'stats', {
        ...state.stats,
        loading: false,
        error: true,
      });

    default:
      return state;
  }
}
