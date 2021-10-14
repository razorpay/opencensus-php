import { set } from 'common/utils/immutable';
import Subscription from 'merchant/models/Subscription';
import { SubscriptionItem } from 'merchant/models/Item';
import {
  makeActionCollectionReducer,
  fetchAll,
  updateEntityInList,
  listFetchSuccessState,
  listFetchErrorState,
} from 'merchant/reducers/collection';
import { makeEntityReducer, updateEntity } from 'merchant_common/reducers/entity';

import { PLAN_FETCH } from 'merchant/reducers/plans';
import { CUSTOMER_FETCH } from 'merchant/reducers/customers';
import { merchantFetch } from 'merchant/utils/ajax';

const ITEMS_FETCH = 'ITEMS_FETCH';
const ITEM_CREATE = 'ITEM_CREATE';
const ITEM_EDIT = 'ITEM_EDIT';
const SUBSCRIPTION_CREATE = 'SUBSCRIPTION_CREATE';
const SUBSCRIPTION_UPDATE = 'SUBSCRIPTION_UPDATE';
const SUBSCRIPTION_DELETE = 'SUBSCRIPTION_DELETE';
const SUBSCRIPTION_CANCEL = 'SUBSCRIPTION_CANCEL';
const SUBSCRIPTION_FETCH = 'SUBSCRIPTION_FETCH';
const SUBSCRIPTION_OFFER_FETCH = 'SUBSCRIPTION_OFFER_FETCH';
const SUBSCRIPTION_INVOICES_FETCH = 'SUBSCRIPTION_INVOICES_FETCH';
const SUBSCRIPTION_SETTINGS = 'SUBSCRIPTION_SETTINGS';
const SUBSCRIPTION_SETTINGS_UPDATE = 'SUBSCRIPTION_SETTINGS_UPDATE';
const CHECKOUT_INFO = 'CHECKOUT_INFO';

export const fetchSubscriptionItems = (params) => {
  const item = new SubscriptionItem();

  return {
    type: ITEMS_FETCH,
    payload: item.fetchAll(params),
  };
};

export const fetchSubscriptionOfferAPI = (payment_methods) => {
  return merchantFetch({
    url: `offers/subscription`,
    method: 'get',
    data: {
      payment_methods,
    },
  });
};

export const fetchSubscriptionOffers = (payment_methods) => {
  return {
    type: SUBSCRIPTION_OFFER_FETCH,
    payload: fetchSubscriptionOfferAPI(payment_methods),
  };
};

export const saveSubscriptionItem = (params) => {
  const item = new SubscriptionItem(params);

  return {
    type: item.isNew ? ITEM_CREATE : ITEM_EDIT,
    payload: item.save(null, {
      headers: {
        'Content-Type': 'application/json',
      },
    }),
  };
};

export const fetchSubscriptionCreditNotes = (id) => {
  return merchantFetch(
    `creditnote?subscription_id=${id}&status[]=processed&status[]=partially_processed`,
  );
};

export const fetchSubscriptions = (params) => fetchAll(params, Subscription, 'SUBSCRIPTIONS');

export const fetchSubscription = (id) => {
  const subscription = new Subscription();
  return {
    type: SUBSCRIPTION_FETCH,
    payload: subscription.fetch(id),
  };
};

export const fetchSubscriptionsOverview = (before) => {
  return merchantFetch({
    url: `subscriptions/overview?before=${before}`,
  });
};

export const fetchInvoices = (subs_id) => {
  const subscription = new Subscription();
  subs_id = subs_id.replace(/\/$/, '');

  return {
    type: SUBSCRIPTION_INVOICES_FETCH,
    payload: subscription.fetchInvoices(subs_id),
  };
};

export const fetchScheduledChanges = (id) => {
  const subscription = new Subscription({ id });

  return subscription.fetchScheduledChanges();
};

export const saveSubscription = (params) => {
  const subscription = new Subscription();
  return {
    type: SUBSCRIPTION_CREATE,
    payload: subscription.save(params),
  };
};

export const updateSubscription = (params) => {
  const subscription = new Subscription(params);
  return {
    type: SUBSCRIPTION_UPDATE,
    payload: subscription.save(params),
  };
};

export const removeOffersOnSubscription = (id, offer_id) => {
  const subscription = new Subscription({
    id,
    offer_id,
  });

  return {
    type: SUBSCRIPTION_UPDATE,
    payload: subscription.removeOffer(),
  };
};

export const cancelUpdateSubscription = (id) => {
  const subscription = new Subscription({ id });

  return subscription.cancelUpdate();
};

export const deleteSubscription = (params) => {
  const subscription = new Subscription(params);

  return {
    type: SUBSCRIPTION_DELETE,
    payload: subscription.delete(),
    id: subscription.id,
  };
};

export const cancelSubscription = ({ id, cancel_at_cycle_end }) => {
  const subscription = new Subscription({ id });

  return {
    type: SUBSCRIPTION_CANCEL,
    payload: subscription.cancel(cancel_at_cycle_end),
  };
};

export const pauseAndResumeSubscription = (data) => {
  const subscription = new Subscription(data);

  return {
    type: SUBSCRIPTION_UPDATE,
    payload: subscription.pauseOrResume(),
  };
};

export const getCheckoutInfo = (id) => {
  return {
    type: CHECKOUT_INFO,
    payload: merchantFetch({
      url: `subscriptions/${id}/checkout_info`,
      data: {
        modify_charge_date: 0,
      },
    }),
  };
};

export const fetchSettings = () => {
  return {
    type: SUBSCRIPTION_SETTINGS,
    payload: merchantFetch({
      url: 'subscriptions/settings',
    }),
  };
};

export const saveSettings = (data) => {
  return {
    type: SUBSCRIPTION_SETTINGS_UPDATE,
    payload: merchantFetch({
      url: 'subscriptions/settings',
      method: 'post',
      data,
    }),
  };
};

export const testChargeSubscription = (subscriptionId, success) => {
  return merchantFetch({
    url: `subscriptions/${subscriptionId}/charge`,
    method: 'post',
    data: {
      success,
    },
  });
};

// Manual Attempt for pending invoice payment
export const paymentManualAttempt = (subscriptionId, invoiceId) =>
  merchantFetch({
    url: `subscriptions/${subscriptionId}/invoices/${invoiceId}/charge`,
    method: 'post',
  });

const entityListInitialState = {
  loading: true,
  items: [],
  error: null,
  settings: {
    loading: true,
    items: [],
    error: null,
  },
  offers: {
    loading: true,
    items: [],
  },
};

// List Reducer
export const subscriptionsReducer = makeActionCollectionReducer(
  'SUBSCRIPTIONS',
  {
    [`${SUBSCRIPTION_CANCEL}::SUCCESS`]: updateEntityInList,
    [`${SUBSCRIPTION_UPDATE}::SUCCESS`]: updateEntityInList,
    [`${SUBSCRIPTION_SETTINGS}::SUCCESS`]: (state, action) => {
      return {
        ...state,
        settings: action.payload.data,
        loading: false,
      };
    },
    [`${SUBSCRIPTION_SETTINGS}::ERROR`]: (state, action) => {
      return {
        ...state,
        settings: {
          ...action.payload.data,
          loading: false,
          error: action.payload.errors,
        },
      };
    },
    [`${SUBSCRIPTION_SETTINGS_UPDATE}::SUCCESS`]: (state, action) => {
      let items = state.settings.items;

      if (items.length === 0) {
        items.push(action.payload.data);
      } else {
        items = items.map((method) => {
          if (method.name === action.payload.data.name) {
            return action.payload.data;
          }

          return method;
        });
      }

      return {
        ...state,
        settings: {
          ...state.settings,
          items,
        },
      };
    },
    [`${SUBSCRIPTION_OFFER_FETCH}::SUCCESS`]: (state, action) =>
      set(state, 'offers', listFetchSuccessState(state.offers, action)),
    [`${SUBSCRIPTION_OFFER_FETCH}::ERROR`]: (state, action) =>
      set(state, 'offers', listFetchErrorState(state.offers, action)),
  },
  entityListInitialState,
);

const updateInvoicesEntity = (status) => (state, action) => {
  switch (status) {
    case 'PENDING':
      return set(state, 'invoices', { loading: true, items: [], error: null });
    case 'SUCCESS':
      return set(state, 'invoices', {
        loading: false,
        items: action.payload.data.items,
        error: null,
      });
    case 'ERROR':
      return set(state, 'invoices', {
        loading: false,
        items: [],
        error: action.payload.errors,
      });
    default: {
      return state;
    }
  }
};

// Details Reducer
const entityInitialState = {
  loading: true,
  entity: {},
  plan: {
    item: {},
  },
  invoices: {
    loading: true,
    items: [],
  },
  customer: {},
  error: null,
};

export const subscriptionReducer = makeEntityReducer(
  SUBSCRIPTION_FETCH,
  {
    [`${SUBSCRIPTION_CANCEL}::SUCCESS`]: updateEntity,
    [`${SUBSCRIPTION_UPDATE}::SUCCESS`]: updateEntity,
    [`${PLAN_FETCH}::SUCCESS`]: (state, action) => {
      return set(state, 'plan', action.payload);
    },
    [`${CUSTOMER_FETCH}::SUCCESS`]: (state, action) => {
      return set(state, 'customer', action.payload);
    },
    [`${SUBSCRIPTION_INVOICES_FETCH}::SUCCESS`]: updateInvoicesEntity('SUCCESS'),
    [`${SUBSCRIPTION_INVOICES_FETCH}::PENDING`]: updateInvoicesEntity('PENDING'),
    [`${SUBSCRIPTION_INVOICES_FETCH}::ERROR`]: updateInvoicesEntity('ERROR'),
  },
  entityInitialState,
);
