import { set, merge } from 'rzp/utils/immutable';
import ajax from 'merchant/utils/ajax';
import GenericEntity from 'merchant/models/GenericEntity';
import Payment from 'merchant/models/Payment';
import Refund from 'merchant/models/Refund';
import Order from 'merchant/models/Order';
import Settlement from 'merchant/models/Settlement';
import Reversal from 'merchant/models/Reversal';
import Transfer from 'merchant/models/Transfer';

const fetchAll = (params, Entity, shouldNameAction) => {
  let entity = new Entity();
  return {
    type: getActionName(shouldNameAction && Entity),
    payload: entity.fetchAll(params),
  };
};

let initialState = {
  loading: true,
  items: [],
  error: null,
};

export function makeCollectionReducer(Entity) {
  var actionName = getActionName(Entity);

  return function(state = initialState, action) {
    switch (action.type) {
      case `${actionName}::PENDING`:
        return initialState;

      case `${actionName}::SUCCESS`:
        let { items } = action.payload.data;
        return merge(state, {
          loading: false,
          payments: action.payload.data.items,
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

const getActionName = (Entity = GenericEntity) => `${Entity.name}_FETCH`;

export default makeCollectionReducer();

export const fetchPayments = params => fetchAll(params, Payment);
export const fetchOrders = params => fetchAll(params, Order);
export const fetchTransfers = params => fetchAll(params, Transfer);
export const fetchReversals = params => fetchAll(params, Reversal);
export const fetchMarketplacePayments = params => {
  params.transferred = 1;
  return fetchAll(params, Payment);
};

export const refundsReducer = makeCollectionReducer(Refund);
export const fetchRefunds = params => fetchAll(params, Refund);

export const settlementsReducer = makeCollectionReducer(Settlement);
export const fetchSettlements = params => fetchAll(params, Settlement);

export const fetchSubscriptions = () => {
  return {
    type: getActionName(),
    payload: ajax('/subscriptions'),
  };
};
