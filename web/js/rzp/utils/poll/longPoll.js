/*
 * This modules defines a function that does LongPolling
 */

// Helper/Private Function
const _poll = options => {
  const {
    fetchFunc,
    validator,
    minWaitTime = 0,
    resolve,
    reject,
    shouldAbortPoll,
  } = options;

  const startTime = Date.now();

  fetchFunc()
    .then(resp => {
      // after resolving network call, if we findout the poll
      // should be aborted, just return;
      if (shouldAbortPoll()) {
        return;
      }

      const shouldResolve = validator(resp);

      if (shouldResolve) {
        resolve(resp);
      } else {
        const diff = startTime - Date.now();

        window.setTimeout(() => {
          // after timer, if we findout the poll
          // should be aborted, just return;
          return !shouldAbortPoll() && _poll(options);
        }, minWaitTime - diff);
      }
    })
    .catch(reject);
};

const poll = options => {
  /*
   * @param {Function} fetchFunc*
   * @param {Function} validator*
   * @param {Number} minWaitTime | 0
   *
   * @returns {Object}
   *
   * `fetchFunc` should return a Promise and `validator` should
   * return Boolean
   *
   * `fetchFunc` is used to fetch the resource and `validator`
   * is called with the response to decide when to stop polling.
   * if calling `validator` returns `true`, the polling is stopped
   * and the reponse is resolved.
   *
   * `minWaitTime` is used to to decide the minimum amount of
   * time to wait between each call, if the request takes more time
   * than `minWaitTime` , the next call is made immediately , else
   * the next call will be made with `minWaitTime` from the previous call
   *
   * The return value is an object with two keys, 
   * `promise` - Promise to be be resolved when polling is stopped
   * `abort` - Function to call to manually stop polling at any point of time
   *  , the promise would never resolve when `abort` is called
   */

  const { fetchFunc, validator } = options;

  if (typeof fetchFunc !== 'function') {
    throw { message: 'fetchFunc need to be a function' };
  }

  if (typeof validator !== 'function') {
    throw { message: 'validator need to be a function' };
  }

  let currentTimer = null,
    isPollAborted = false;

  const shouldAbortPoll = () => {
    return isPollAborted;
  };

  const promise = new Promise((resolve, reject) => {
    _poll({
      ...options,
      resolve,
      reject,
      shouldAbortPoll,
    });
  });

  const abort = () => {
    isPollAborted = true;
  };

  return {
    promise,
    abort,
  };
};

export default poll;
