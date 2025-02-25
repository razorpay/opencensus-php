import { syncReduxWithShellZustand } from './syncReduxWithShellZustand';
import { ShellZustandToReduxKeys } from './config';
import React from 'react';

interface ShellZustandToReduxSyncProviderProps {
  store: any; // Define the type for your Redux store, if known
  children: JSX.Element; // Children elements to be rendered
}

/**
 * A provider component that synchronizes Zustand state with Redux store.
 *  
 * @example
 * <ShellZustandToReduxSyncProvider store={myReduxStore}>
 *   <MyComponent />
 * </ShellZustandToReduxSyncProvider>
 */
export const ShellZustandToReduxSyncProvider = ({ store, children }: ShellZustandToReduxSyncProviderProps): JSX.Element => {
  syncReduxWithShellZustand(store, ShellZustandToReduxKeys);
  return <>{children}</>; // Ensure children are wrapped in a fragment for valid JSX
};
