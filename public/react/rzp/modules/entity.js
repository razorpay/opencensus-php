import { set, merge } from 'rzp/utils/immutable';

let defaultInitialState = {
  loading: true,
  entity: {},
  error: null,
};

export const entityFetchPendingState = (state, action) =>
  set(state, 'loading', true);

export const entityFetchSuccessState = (state, action) => {
  return merge(state, {
    loading: false,
    entity: action.payload,
    error: null,
  });
};

export const entityFetchErrorState = (state, action, initialState) => {
  return merge(state, {
    loading: false,
    error: action.payload.errors,
    entity: initialState.entity,
  });
};

export const updateEntity = (state, action) => {
  let entity = merge(state.entity, action.payload);
  return set(state, 'entity', entity);
};

export const makeEntityReducer = (
  fetchActionName,
  actionHandlers = {},
  initialState = defaultInitialState
) => {
  const defaultHandlers = {
    [`${fetchActionName}::PENDING`]: entityFetchPendingState,
    [`${fetchActionName}::SUCCESS`]: entityFetchSuccessState,
    [`${fetchActionName}::ERROR`]: entityFetchErrorState,
  };

  const handlers = { ...defaultHandlers, ...actionHandlers };

  return (state = initialState, action) => {
    if (handlers.hasOwnProperty(action.type)) {
      return handlers[action.type](state, action, initialState);
    } else {
      return state;
    }
  };
};
