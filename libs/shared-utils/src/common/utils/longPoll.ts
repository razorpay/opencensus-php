/*
 * This module defines a function that does LongPolling
 */

// Define the shape of the options for the polling functions
interface PollOptions<T> {
    fetchFunc: () => Promise<T>;
    validator: (response: T) => boolean;
    resolve?: (value: T) => void;
    reject?: (reason?: any) => void;
    shouldAbortPoll?: () => boolean;
    getNextCallWaitime?: () => number;
    minWaitTime?: number;
  }
  
  // Helper/Private Function
  const _poll = <T>(options: PollOptions<T>): void => {
    const {
      fetchFunc,
      validator,
      resolve,
      reject,
      shouldAbortPoll = () => false,
      getNextCallWaitime,
      minWaitTime = 0,
    } = options;
  
    const startTime = Date.now();
  
    fetchFunc()
      .then((resp: T) => {
        // After resolving network call, if we find out the poll
        // should be aborted, just return;
        if (shouldAbortPoll()) {
          return;
        }
  
        const shouldResolve = validator(resp);
  
        if (shouldResolve) {
          resolve?.(resp);
        } else {
          const diff = startTime - Date.now(),
            waitTime = (getNextCallWaitime ? getNextCallWaitime() : minWaitTime) - diff;
  
          window.setTimeout(() => {
            // After timer, if we find out the poll
            // should be aborted, just return;
            if (!shouldAbortPoll()) {
              _poll(options);
            }
          }, waitTime);
        }
      })
      .catch((error: any) => {
        reject?.(error);
      });
  };
  
  // Define the return type of the poll function
  interface PollReturn<T> {
    promise: Promise<T>;
    abort: () => void;
  }
  
  /**
   * Initiates a long polling process.
   *
   * @param options - Configuration options for polling.
   * @param options.fetchFunc - Function to fetch the resource, should return a Promise.
   * @param options.validator - Function to validate the response, should return a boolean.
   * @param options.minWaitTime - Minimum time to wait between polls in milliseconds (default: 0).
   *
   * @returns An object containing:
   * - `promise`: A Promise that resolves when polling is stopped.
   * - `abort`: A function to manually stop polling at any point.
   */
  export const longPoll = <T>(options: {
    fetchFunc: () => Promise<T>;
    validator: (response: T) => boolean;
    minWaitTime?: number;
  }): PollReturn<T> => {
    const { fetchFunc, validator, minWaitTime = 0 } = options;
  
    if (typeof fetchFunc !== 'function') {
      throw new Error('fetchFunc needs to be a function');
    }
  
    if (typeof validator !== 'function') {
      throw new Error('validator needs to be a function');
    }
  
    let isPollAborted = false;
  
    const shouldAbortPoll = (): boolean => {
      return isPollAborted;
    };
  
    const promise: Promise<T> = new Promise<T>((resolve, reject) => {
      _poll<T>({
        ...options,
        resolve,
        reject,
        shouldAbortPoll,
      });
    });
  
    const abort = (): void => {
      isPollAborted = true;
    };
  
    return {
      promise,
      abort,
    };
  };
  
  