import Subscription from 'merchant/models/Subscription';
import { makeActionCollectionReducer, fetchAll } from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';

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

// // Virtual Accounts Details Reducer
// let detailsInitialState = {
//   loading: true,
//   entity: {},
//   error: null,
//   va_payments: [],
// };
// export const virtualAccountReducer = makeEntityReducer(
//   VIRTUAL_ACCOUNT_FETCH,
//   {
//     [`${VIRTUAL_ACCOUNT_EDIT}::SUCCESS`]: updateEntity,

//     [`${VIRTUAL_ACCOUNT_PAYMENTS_FETCH}::SUCCESS`]: (state, action) => {
//       return set(state, 'va_payments', action.payload.data.items);
//     },
//   },
//   detailsInitialState
// );
