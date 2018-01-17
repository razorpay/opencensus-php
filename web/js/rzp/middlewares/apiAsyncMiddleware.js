// kinda `redux-promise` catered to our needs.

export default ({ dispatch, getState }) => {
  return next => action => {
    // Handles thunk like functionality-1
    if (typeof action === 'function') {
      return action(dispatch, getState);
    }

    const { payload, type, ...rest } = action;

    // Handles thunk like functionality-2
    if (!payload || typeof action.payload.then !== 'function') {
      return next(action);
    }

    next({
      type: `${type}::PENDING`,
      ...rest,
    });

    const actionPromise = payload;
    actionPromise
      .then(response =>
        next({
          type: `${type}::SUCCESS`,
          payload: response,
          ...rest,
        })
      )
      .catch(error => {
        next({
          type: `${type}::ERROR`,
          payload: error,
          error: true,
          ...rest,
        });
        throw error;
      });

    return actionPromise;
  };
};
