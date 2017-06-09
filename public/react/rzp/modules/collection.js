import { set, merge } from 'rzp/utils/immutable';
import ajax from 'merchant/utils/ajax';
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
    type: getActionName(Entity, namespace),
    payload: entity.fetchAll(params),
  };
};

let initialState = {
  loading: true,
  items: [],
  error: null,
};

export function makeCollectionReducer(Entity, namespace) {
  var actionName = getActionName(Entity, namespace);

  return function(state = initialState, action) {
    switch (action.type) {
      case `${actionName}_RESET`:
      case `${actionName}::PENDING`:
        return initialState;

      case `${actionName}::SUCCESS`:
        let { items } = action.payload.data;
        return merge(state, {
          loading: false,
          items,
          error: null,
        });

      case `${actionName}::ERROR`:
        return merge(state, {
          loading: false,
          error: action.payload.errors,
        });

      default:
        return state;
    }
  };
}

export const getActionName = (Entity = GenericEntity, namespace) => {
  var action = [Entity.name || Entity, 'FETCH_COLLECTION'];
  if (namespace) {
    action.unshift(namespace);
  }
  return action.join('_');
};

// export default makeCollectionReducer();

export const fetchPayments = params => fetchAll(params, Payment);
export const paymentsReducer = makeCollectionReducer(Payment);

export const fetchOrders = params => fetchAll(params, Order);
export const ordersReducer = makeCollectionReducer(Order);

export const fetchTransfers = params => fetchAll(params, Transfer);
export const transfersReducer = makeCollectionReducer(Transfer);

export const fetchReversals = params => fetchAll(params, Reversal);
export const reversalsReducer = makeCollectionReducer(Reversal);

export const fetchMarketplacePayments = params => {
  params.transferred = 1;
  return fetchAll(params, Payment, 'MP');
};
export const mpPaymentsReducer = makeCollectionReducer(Payment, 'MP');

export const refundsReducer = makeCollectionReducer(Refund);
export const fetchRefunds = params => fetchAll(params, Refund);

export const settlementsReducer = makeCollectionReducer(Settlement);
export const fetchSettlements = params => fetchAll(params, Settlement);

export const fetchSubscriptions = () => {
  return {
    type: getActionName('SUBSCRIPTIONS'),
    payload: ajax('/subscriptions'),
  };
};
export const subscriptionsReducer = makeCollectionReducer('SUBSCRIPTIONS');
