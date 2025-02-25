const CHUNK_RETRY_INTERVAL = 1000;
const RETRY_TIMES = 3;

/**
 * Safely imports a component asynchronously with retries to handle chunk load failures.
 *
 * @param fn - A function that returns a promise, typically a dynamic import for a React component.
 * @param retriesLeft - The number of retry attempts left. Defaults to `RETRY_TIMES`.
 * @returns A promise that resolves with the loaded component or rejects with an error.
 *
 * @example
 * const loadComponent = () => import('./MyComponent');
 *
 * safeChunkImport(loadComponent)
 *   .then((Component) => {
 *     // Use the dynamically loaded Component
 *     // Example: <Component />
 *   })
 *   .catch((error) => {
 *     console.error('Failed to load component:', error);
 *   });
 */
export const safeChunkImport = <T>(
  fn: () => Promise<T>,
  retriesLeft: number = RETRY_TIMES
): Promise<T> => {
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
            // Max retries failed, go on with the normal reject flow
            reject(error);
            return;
          }

          // Retry on failure
          safeChunkImport(fn, retriesLeft - 1).then(resolve, reject);
        }, CHUNK_RETRY_INTERVAL);
      });
  });
};


