import { set, merge } from 'rzp/utils/immutable';
import GenericEntity from 'merchant/models/GenericEntity';
import Payment from 'merchant/models/Payment';
import Reversal from 'merchant/models/Reversal';
import Transfer from 'merchant/models/Transfer';

const GENERIC_ENTITY = 'ENTITY';
const FETCH_COLLECTION = 'FETCH';

export const fetchAll = (params, Entity = GenericEntity, namespace) => {
  return dispatch => {
    let entity = new Entity();
    return dispatch({
      type: nameFetchAction(Entity, namespace),
      payload: entity.fetchAll(params),
    });
  };
};

let initialState = {
  loading: true,
  items: [],
  error: null,
};

export function makeCollectionReducer(Entity, namespace) {
  var actionName = nameFetchAction(Entity, namespace);

  return function(state = initialState, action) {
    switch (action.type) {
      case `${actionName}::PENDING`:
        return set(state, 'loading', true);

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

function nameFetchAction(Entity, namespace) {
  var name = [Entity.name, FETCH_COLLECTION];
  if (namespace) {
    name.unshift(namespace);
  }
  return name.join('_');
}

export default makeCollectionReducer(GenericEntity);

export const paymentsReducer = makeCollectionReducer(Payment);
export const fetchPayments = params => fetchAll(params, Payment);

export const mpPaymentsReducer = makeCollectionReducer(Payment, 'MP');
export const fetchMarketplacePayments = params => {
  params.transferred = 1;
  return fetchAll(params, Payment, 'MP');
};

export const transfersReducer = makeCollectionReducer(Transfer);
export const fetchTransfers = params => fetchAll(params, Transfer);

export const reversalsReducer = makeCollectionReducer(Reversal);
export const fetchReversals = params => fetchAll(params, Reversal);
