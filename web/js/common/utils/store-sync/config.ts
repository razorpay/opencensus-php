/**
 * Keys to synchronize state from Redux to Zustand.
 *
 * @constant {string[]} ReduxToShellZustandKeys
 */
export const ReduxToShellZustandKeys: string[] = ['session.mode', 'session.user', 'session.org'];

/**
 * Keys to synchronize state from Zustand to Redux.
 *
 * @constant {string[]} ShellZustandToReduxKeys
 */
export const ShellZustandToReduxKeys: string[] = ['session.mode'];
