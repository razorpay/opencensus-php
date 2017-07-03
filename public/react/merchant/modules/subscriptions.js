import { set } from 'rzp/utils/immutable';
import Subscription from 'merchant/models/Subscription';
import { makeActionCollectionReducer, fetchAll } from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';

import { PLAN_FETCH } from 'merchant/modules/plans';
import { CUSTOMER_FETCH } from 'merchant/modules/customers';

const SUBSCRIPTIONS_FETCH = 'SUBSCRIPTIONS_FETCH';
const SUBSCRIPTION_CREATE = 'SUBSCRIPTION_CREATE';
const SUBSCRIPTION_EDIT = 'SUBSCRIPTION_EDIT';
const SUBSCRIPTION_DELETE = 'SUBSCRIPTION_DELETE';
const SUBSCRIPTION_FETCH = 'SUBSCRIPTION_FETCH';

export const fetchSubscriptions = params =>
  fetchAll(params, Subscription, 'SUBSCRIPTIONS');

export const fetchSubscription = id => {
  let subscription = new Subscription();
  return {
    type: SUBSCRIPTION_FETCH,
    payload: subscription.fetch(id),
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

// List Reducer
export const subscriptionsReducer = makeActionCollectionReducer(
  'SUBSCRIPTIONS'
);

// Details Reducer
let entityInitialState = {
  loading: true,
  entity: {},
  plan: {},
  customer: {},
  error: null,
};

export const subscriptionReducer = makeEntityReducer(
  SUBSCRIPTION_FETCH,
  {
    [`${PLAN_FETCH}::SUCCESS`]: (state, action) => {
      return set(state, 'plan', action.payload);
    },
    [`${CUSTOMER_FETCH}::SUCCESS`]: (state, action) => {
      return set(state, 'customer', action.payload);
    },
  },
  entityInitialState
);
