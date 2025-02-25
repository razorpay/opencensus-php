import { useEffect } from 'react';
import { useStore as zustandStore } from '@federated/apps/shell/commonStore'; // Import Zustand store from Host
import isEqual from 'lodash/isEqual';
import merge from 'lodash/merge';
import isEmpty from 'lodash/isEmpty';
import get from 'lodash/get';
import set from 'lodash/set';


export const SHELL_ZUSTAND_TO_REDUX_SYNC_ACTION_TYPE = '@SHELL_ZUSTAND_TO_REDUX_SYNC';

type KeysType = string[]; // Define a type for the keys array

const getNestedValue = (obj: Record<string, any>, path: string): any => get(obj, path);
const setNestedValue = (obj: Record<string, any>, path: string, value: any): void => set(obj, path, value);

/**
 * Syncs Redux state with Zustand for specific keys.
 * 
 * @param {any} reduxStore - The Redux store to sync with.
 * @param {KeysType} keys - An array of keys to sync.
 */
export const syncReduxWithShellZustand = (reduxStore: any, keys: KeysType): void => {
  useEffect(() => {
    let prevState = zustandStore.getState();

    /**
     * Checks if the relevant state keys have changed.
     * 
     * @param {Record<string, any>} prevState - The previous Zustand state.
     * @param {Record<string, any>} newState - The new Zustand state.
     * @param {KeysType} keys - An array of keys to check.
     * @returns {boolean} - Returns true if there are relevant changes, false otherwise.
     */
    const hasRelevantChanges = (prevState: Record<string, any>, newState: Record<string, any>, keys: KeysType): boolean => {
      return keys.some(
        (key) => !isEqual(getNestedValue(prevState, key), getNestedValue(newState, key)),
      );
    };

    /**
     * Creates an update object with only the changed keys.
     * 
     * @param {Record<string, any>} prevState - The previous Zustand state.
     * @param {Record<string, any>} newState - The new Zustand state.
     * @param {KeysType} keys - An array of keys to include in the update.
     * @returns {Record<string, any>} - The update object with changed keys.
     */
    const createStateUpdate = (prevState: Record<string, any>, newState: Record<string, any>, keys: KeysType): Record<string, any> => {
      return keys.reduce((update, key) => {
        const newValue = getNestedValue(newState, key);
        if (!isEqual(newValue, getNestedValue(prevState, key))) {
          setNestedValue(update, key, newValue);
        }
        return update;
      }, {});
    };

    /**
     * Syncs the Zustand state with Redux for specific keys.
     * 
     * @param {Record<string, any>} newState - The new Zustand state.
     */
    const syncState = (newState: Record<string, any>): void => {
      if (hasRelevantChanges(prevState, newState, keys)) {
        const stateUpdate = createStateUpdate(prevState, newState, keys);
        if (!isEmpty(stateUpdate)) {
          reduxStore.dispatch({
            type: SHELL_ZUSTAND_TO_REDUX_SYNC_ACTION_TYPE,
            payload: stateUpdate,
          });
        }
        prevState = newState;
      }
    };

    // Initial sync
    syncState(zustandStore.getState());

    const unsubscribe = zustandStore.subscribe(syncState);
    return () => unsubscribe();
  }, [zustandStore, reduxStore, keys]);
};

/**
 * Attaches Zustand to Redux sync action by merging the new state with the current state.
 * 
 * @param {Function} reducers - The original Redux reducers.
 * @returns {Function} - A function that handles state updates based on actions.
 */
export const attachZustandToReduxSyncAction = (reducers: Function) => (state: any, action: { type: string; payload: Record<string, any> }) => {
  if (action.type === SHELL_ZUSTAND_TO_REDUX_SYNC_ACTION_TYPE) {
    return merge({}, state, action.payload); // Merges the new state with the current state
  }
  return reducers(state, action);
};
