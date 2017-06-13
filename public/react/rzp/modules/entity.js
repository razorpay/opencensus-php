import { set, merge } from 'rzp/utils/immutable';

let defaultInitialState = {
  loading: true,
  entity: {},
  error: null,
};

export default function makeEntityReducer(
  actionName,
  initialState = defaultInitialState
) {
  return (state = initialState, action) => {
    switch (action.type) {
      case `${actionName}::PENDING`:
        return set(state, 'loading', true);

      case `${actionName}::SUCCESS`:
        return merge(state, {
          loading: false,
          entity: action.payload,
          error: null,
        });

      case `${actionName}::ERROR`:
        return merge(state, {
          loading: false,
          error: action.payload.errors,
          entity: initialState.entity,
        });

      default:
        return state;
    }
  };
}
