import { useStore } from '@federated/apps/shell/commonStore';

const REDUCERS_TO_SYNC = ['session', 'app'];

// for UTs initial state
const initialStateSyncMiddleware = (initialState = {}) => {
  REDUCERS_TO_SYNC.forEach((reducerKey) => {
    if (initialState.hasOwnProperty(reducerKey)) {
      useStore.setState({ [reducerKey]: initialState[reducerKey] });
    }
  });
};

export const getSanitizedState = (initialState = {}) => {
  initialStateSyncMiddleware(initialState);
  return Object.keys(initialState).reduce(
    (accumulator, state) => {
      if (REDUCERS_TO_SYNC.indexOf(state) === -1) {
        accumulator.reduxInitialState[state] = initialState[state];
      }
      return accumulator;
    },
    {
      reduxInitialState: {},
    },
  );
};
