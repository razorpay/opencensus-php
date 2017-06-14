import { set, merge } from 'rzp/utils/immutable';

let defaultInitialState = {
  loading: true,
  entity: {},
  error: null,
};

const fetchPendingState = (state, action) => set(state, 'loading', true);

const fetchSuccessState = (state, action) =>
  merge(state, {
    loading: false,
    entity: action.payload,
    error: null,
  });

const fetchErrorState = (state, action) =>
  merge(state, {
    loading: false,
    error: action.payload.errors,
    entity: initialState.entity,
  });

export default function makeEntityReducer(
  fetchActionName,
  actionHandlers = {},
  initialState = defaultInitialState
) {
  const defaultHandlers = {
    [`${fetchActionName}::PENDING`]: fetchPendingState,
    [`${fetchActionName}::SUCCESS`]: fetchSuccessState,
    [`${fetchActionName}::ERROR`]: fetchErrorState,
  };

  const handlers = { ...defaultHandlers, ...actionHandlers };

  return (state = initialState, action) => {
    if (handlers.hasOwnProperty(action.type)) {
      return handlers[action.type](state, action);
    } else {
      return state;
    }
  };
}
