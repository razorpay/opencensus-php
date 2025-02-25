import { DASHBOARD_FEDERATED_MODULES } from '../../../constants';

/**
 * Generates a dynamic promise for loading a remote module.
 * This is to be used in the Webpack `remotes` section.
 *
 * @param {string} url - The full URL to the remote entry file.
 * @param {string} remoteKey - The key for the remote module (used for referencing it on window).
 * @param {number} retries - The number of retries in case the remote fails to load.
 * @param {number} timeout - Timeout in milliseconds for loading the remote.
 * @returns {string} - A stringified dynamic promise to load the remote module.
 */
export const generateRemotePromise = (
  url: string,
  remoteKey: DASHBOARD_FEDERATED_MODULES,
  retries = 3,
  timeout = 15000,
) => {
  return `promise new Promise((resolve, reject) => {
      mountRemoteSafely('${url}', '${remoteKey}', ${retries}, ${timeout})
        .then((container) => {
          resolve(container);
        })
        .catch((error) => {
          console.error('Failed to load ${remoteKey}:', error);
          reject(error);
        });
    })`;
};
