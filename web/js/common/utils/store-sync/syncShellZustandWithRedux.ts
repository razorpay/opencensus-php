import { ReduxToShellZustandKeys } from './config';
import { SHELL_ZUSTAND_TO_REDUX_SYNC_ACTION_TYPE } from './syncReduxWithShellZustand';
import get from 'lodash/get';
import set from 'lodash/set';
import isEqual from 'lodash/isEqual';

type State = Record<string, any>; // Define a type for state objects

/**
 * Deeply updates the Zustand store with values from the Redux state for specified keys.
 *
 * @param {import('zustand').StoreApi<State>} zustandStore - The Zustand store instance.
 * @param {State} reduxState - The current state from Redux.
 * @param {State} zustandState - The current state from Zustand.
 * @param {string[]} keysToSync - An array of keys to synchronize.
 */
const deepUpdate = (
  zustandStore: any,
  reduxState: State,
  zustandState: State,
  keysToSync: string[],
): void => {
  const updatedState = zustandStore.getState();

  keysToSync.forEach((key) => {
    const reduxValue = get(reduxState, key);
    const zustandValue = get(zustandState, key);

    if (!isEqual(reduxValue, zustandValue)) {
      set(updatedState, key, reduxValue);
    }
  });

  zustandStore.setState(updatedState);
};

/**
 * Middleware function to synchronize Zustand with Redux on action dispatch.
 *
 * @param {import('zustand').StoreApi<State>} zustandStore - The Zustand store instance.
 * @param {string[]} keysToSync - An array of keys to synchronize between Redux and Zustand.
 * @returns {Function} - A middleware function for Redux.
 */
const syncShellZustandWithRedux =
  (zustandStore: any, keysToSync: string[]) =>
  (store: any) =>
  (next: Function) =>
  (action: { type: string }) => {
    const result = next(action);

    if (action.type !== SHELL_ZUSTAND_TO_REDUX_SYNC_ACTION_TYPE) {
      const reduxState = store.getState();
      const zustandState = zustandStore.getState();

      deepUpdate(zustandStore, reduxState, zustandState, keysToSync);
    }

    return result;
  };

/**
 * Creates a middleware for synchronizing Zustand with Redux.
 *
 * @param {import('zustand').StoreApi<State>} zustandStore - The Zustand store instance.
 * @returns {Function} - A Redux middleware function.
 */
export const syncShellZustandWithReduxMiddleware = (zustandStore: any, initialState?: any) => {
  // If initial state is provided, update the Zustand store with the initial state values (Will be called during middleware initailization), Used in case UTs
  if (initialState) {
    const zustandState = zustandStore.getState();
    deepUpdate(zustandStore, initialState, zustandState, ReduxToShellZustandKeys);
  }

  return syncShellZustandWithRedux(zustandStore, ReduxToShellZustandKeys);
};
