import { merge, remove, set, unshift } from 'rzp/utils/immutable';
import createReducer from 'rzp/modules/createReducer';

import Settlement from 'merchantLA/models/Settlement';
import Reversal from 'merchantLA/models/Reversal';
import Transfer from 'merchantLA/models/Transfer';

// useEntityReducer tells whether to use common reducer or entity-specific
export const fetchAll = (params, Entity, namespace) => {
  let entity = new Entity();
  return {
    type: getActionName(namespace),
    payload: entity.fetchAll(params),
  };
};

let defaultInitialState = {
  loading: true,
  items: [],
  error: null,
};

export const listFetchPendingState = (state, action, initialState) => {
  return initialState;
};

export const listFetchSuccessState = (state, action) => {
  return merge(state, {
    loading: false,
    items: action.payload.data.items,
    error: null,
  });
};

export const listFetchErrorState = (state, action, initialState) => {
  return merge(state, {
    loading: false,
    items: initialState.items,
    error: action.payload.errors,
  });
};

export const appendEntityToList = (state, action) => {
  return set(state, 'items', unshift(state.items, action.payload));
};

export const updateEntityInList = (state, action) => {
  let itemIndex = state.items.findIndex(item => item.id === action.payload.id);
  return set(state, `items.${itemIndex}`, action.payload);
};

export const removeEntityFromList = (state, action) => {
  let itemsList = remove(state.items, item => item.id === action.id);
  return set(state, 'items', itemsList);
};

export const getActionName = namespace => {
  return namespace + '_FETCH';
};

export const makeCollectionReducer = (
  namespace,
  actionHandlers = {},
  initialState = defaultInitialState
) => {
  let fetchActionName = getActionName(namespace);
  const defaultHandlers = {
    [`${fetchActionName}::PENDING`]: listFetchPendingState,
    [`${fetchActionName}::SUCCESS`]: listFetchSuccessState,
    [`${fetchActionName}::ERROR`]: listFetchErrorState,
  };

  const handlers = { ...defaultHandlers, ...actionHandlers };
  return createReducer({
    handlers,
    initialState,
  });
};

export const makeActionCollectionReducer = (
  namespace,
  actionHandlers = {},
  initialState = defaultInitialState
) => {
  let singularNamespace = namespace.slice(0, namespace.length - 1);

  const defaultHandlers = {
    [`${singularNamespace}_CREATE::SUCCESS`]: appendEntityToList,
    [`${singularNamespace}_EDIT::SUCCESS`]: updateEntityInList,
    [`${singularNamespace}_DELETE::SUCCESS`]: removeEntityFromList,
  };

  const handlers = { ...defaultHandlers, ...actionHandlers };
  return makeCollectionReducer(namespace, handlers, initialState);
};

export const fetchTransfers = params => fetchAll(params, Transfer, 'TRANSFERS');
export const transfersReducer = makeCollectionReducer('TRANSFERS');

export const fetchReversals = params => fetchAll(params, Reversal, 'REVERSALS');
export const reversalsReducer = makeCollectionReducer('REVERSALS');

export const fetchMarketplacePayments = params => {
  params.transferred = 1;
  return fetchAll(params, Payment, 'MP_PAYMENTS');
};

export const fetchSettlements = params =>
  fetchAll(params, Settlement, 'SETTLEMENTS');
export const settlementsReducer = makeCollectionReducer('SETTLEMENTS');
