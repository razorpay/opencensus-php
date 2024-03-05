import { lazy } from 'react';

const CHUNK_RETRY_INTERVAL = 1000;
const RETRY_TIMES = 3;

// Retry chunk loading to avoid chunkloadfailed errors
export const lazyRetry = (fn, retriesLeft = RETRY_TIMES) => {
  return new Promise((resolve, reject) => {
    // Started loading component
    fn()
      .then((res) => {
        // Finished loading component
        resolve(res);
      })
      .catch((error) => {
        // Failed loading component
        setTimeout(() => {
          if (retriesLeft === 1) {
            // Max retry failed, go on with the normal reject flow and report error to sentry
            reject(error);
            return;
          }

          // Retry on failure
          lazyRetry(fn, retriesLeft - 1).then(resolve, reject);
        }, CHUNK_RETRY_INTERVAL);
      });
  });
};

export default (fn) => {
  // Registered the lazyload
  return lazy(() => lazyRetry(fn));
};
