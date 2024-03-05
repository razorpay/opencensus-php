import { useStore, clearStore } from 'shell/commonStore';

const REDUCERS_TO_SYNC = ['session', 'app'];

const stateSyncMiddleware = (store) => (next) => (action) => {
  // Get the current state before the action is processed
  const prevReduxState = store.getState();

  // Call the next middleware or the reducer to process the action
  const result = next(action);

  // Get the updated state after the action is processed
  const nextReduxState = store.getState();

  // Synchronize state for specific reducers
  REDUCERS_TO_SYNC.forEach((reducerKey) => {
    if (JSON.stringify(prevReduxState[reducerKey]) !== JSON.stringify(nextReduxState[reducerKey])) {
      // console.log(`Reducer "${reducerKey}" updated. New state:`, nextReduxState[reducerKey]);
      useStore.setState({ [reducerKey]: nextReduxState[reducerKey] });
    }
  });

  return result;
};

export const syncInitialReduxState = (store) => {
  const initialState = store.getState();
  REDUCERS_TO_SYNC.forEach((reducerKey) => {
    if (initialState.hasOwnProperty(reducerKey)) {
      useStore.setState({ [reducerKey]: initialState[reducerKey] });
    }
  });
};

// for UTs initial state
export const initialStateSyncMiddleware = (initialState = {}) => {
  clearStore();
  REDUCERS_TO_SYNC.forEach((reducerKey) => {
    if (initialState.hasOwnProperty(reducerKey)) {
      useStore.setState({ [reducerKey]: initialState[reducerKey] });
    }
  });
};

export default stateSyncMiddleware;
