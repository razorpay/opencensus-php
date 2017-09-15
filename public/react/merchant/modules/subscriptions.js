import { set } from 'rzp/utils/immutable';
import Subscription from 'merchant/models/Subscription';
import {
  makeActionCollectionReducer,
  fetchAll,
  updateEntityInList,
} from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';

import { PLAN_FETCH } from 'merchant/modules/plans';
import { CUSTOMER_FETCH } from 'merchant/modules/customers';

const SUBSCRIPTIONS_FETCH = 'SUBSCRIPTIONS_FETCH';
const SUBSCRIPTION_CREATE = 'SUBSCRIPTION_CREATE';
const SUBSCRIPTION_EDIT = 'SUBSCRIPTION_EDIT';
const SUBSCRIPTION_DELETE = 'SUBSCRIPTION_DELETE';
const SUBSCRIPTION_CANCEL = 'SUBSCRIPTION_CANCEL';
const SUBSCRIPTION_FETCH = 'SUBSCRIPTION_FETCH';
const SUBSCRIPTION_INVOICES_FETCH = 'SUBSCRIPTION_INVOICES_FETCH';

export const fetchSubscriptions = params =>
  fetchAll(params, Subscription, 'SUBSCRIPTIONS');

export const fetchSubscription = id => {
  let subscription = new Subscription();
  return {
    type: SUBSCRIPTION_FETCH,
    payload: subscription.fetch(id),
  };
};

export const fetchInvoices = subs_id => {
  let subscription = new Subscription();
  subs_id = subs_id.replace(/\/$/, '');

  return {
    type: SUBSCRIPTION_INVOICES_FETCH,
    payload: subscription.fetchInvoices(subs_id),
  };
};

export const saveSubscription = params => {
  const subscription = new Subscription(params);
  return {
    type: subscription.isNew ? SUBSCRIPTION_CREATE : SUBSCRIPTION_EDIT,
    payload: subscription.save(),
  };
};

export const deleteSubscription = params => {
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

// List Reducer
export const subscriptionsReducer = makeActionCollectionReducer(
  'SUBSCRIPTIONS',
  {
    [`${SUBSCRIPTION_CANCEL}::SUCCESS`]: updateEntityInList,
  }
);

const updateInvoicesEntity = status => (state, action) => {
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
  }
};

// Details Reducer
let entityInitialState = {
  loading: true,
  entity: {},
  plan: {
    item: {},
  },
  invoices: {
    loading: false,
    items: [],
  },
  customer: {},
  error: null,
};

export const subscriptionReducer = makeEntityReducer(
  SUBSCRIPTION_FETCH,
  {
    [`${SUBSCRIPTION_CANCEL}::SUCCESS`]: updateEntity,
    [`${PLAN_FETCH}::SUCCESS`]: (state, action) => {
      return set(state, 'plan', action.payload);
    },
    [`${CUSTOMER_FETCH}::SUCCESS`]: (state, action) => {
      return set(state, 'customer', action.payload);
    },
    [`${SUBSCRIPTION_INVOICES_FETCH}::SUCCESS`]: updateInvoicesEntity(
      'SUCCESS'
    ),
    [`${SUBSCRIPTION_INVOICES_FETCH}::PENDING`]: updateInvoicesEntity(
      'PENDING'
    ),
    [`${SUBSCRIPTION_INVOICES_FETCH}::ERROR`]: updateInvoicesEntity('ERROR'),
  },
  entityInitialState
);
