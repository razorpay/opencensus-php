import ajax from 'merchant/utils/ajax';
import { set, merge, unshift, remove } from 'rzp/utils/immutable';

import GenericEntity from 'merchant/models/GenericEntity';
import Payment from 'merchant/models/Payment';
import Refund from 'merchant/models/Refund';
import Order from 'merchant/models/Order';
import Settlement from 'merchant/models/Settlement';
import Reversal from 'merchant/models/Reversal';
import Transfer from 'merchant/models/Transfer';

// useEntityReducer tells whether to use common reducer or entity-specific
export const fetchAll = (params, Entity, namespace) => {
  let entity = new Entity();
  return {
    type: `${namespace}_FETCH`,
    payload: entity.fetchAll(params),
  };
};

let initialState = {
  loading: true,
  items: [],
  error: null,
};

export function makeCollectionReducer(actionName) {
  return function(state = initialState, action) {
    switch (action.type) {
      case `${actionName}_FETCH_RESET`:
      case `${actionName}_FETCH::PENDING`:
        return initialState;

      case `${actionName}_FETCH::SUCCESS`:
        let { items } = action.payload.data;
        return merge(state, {
          loading: false,
          items,
          error: null,
        });

      case `${actionName}_FETCH::ERROR`:
        return merge(state, {
          loading: false,
          error: action.payload.errors,
        });

      case `${actionName}_CREATE::SUCCESS`:
        return set(state, 'items', unshift(state.items, action.payload));

      default:
        return state;
    }
  };
}

// export default makeCollectionReducer();

export const fetchPayments = params => fetchAll(params, Payment, 'PAYMENTS');
export const paymentsReducer = makeCollectionReducer('PAYMENTS');

export const fetchOrders = params => fetchAll(params, Order, 'ORDERS');
export const ordersReducer = makeCollectionReducer('ORDERS');

export const fetchTransfers = params => fetchAll(params, Transfer, 'TRANSFERS');
export const transfersReducer = makeCollectionReducer('TRANSFERS');

export const fetchReversals = params => fetchAll(params, Reversal, 'REVERSALS');
export const reversalsReducer = makeCollectionReducer('REVERSALS');

export const fetchMarketplacePayments = params => {
  params.transferred = 1;
  return fetchAll(params, Payment, 'MP_PAYMENTS');
};
export const mpPaymentsReducer = makeCollectionReducer('MP_PAYMENTS');

export const fetchRefunds = params => fetchAll(params, Refund, 'REFUNDS');
export const refundsReducer = makeCollectionReducer('REFUNDS');

export const fetchSettlements = params =>
  fetchAll(params, Settlement, 'SETTLEMENTS');
export const settlementsReducer = makeCollectionReducer('SETTLEMENTS');

export const fetchSubscriptions = () => {
  return {
    type: 'SUBSCRIPTIONS',
    payload: ajax('/subscriptions'),
  };
};
export const subscriptionsReducer = makeCollectionReducer('SUBSCRIPTIONS');
