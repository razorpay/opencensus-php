/**
 * Checks if the current environment is production based on the global `window.APP_ENV` variable.
 *
 * @returns {boolean} - Returns `true` if the environment is production, otherwise `false`.
 */
export const isProductionEnv = (): boolean => window.APP_ENV === 'production';
