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
