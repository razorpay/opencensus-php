import Log from 'merchantLA/models/Reports/Log';
import {
  listFetchSuccessState,
  getActionName as getFetchActionName,
  makeActionCollectionReducer,
  updateEntityInList,
  appendEntityToList,
  fetchAll,
} from 'merchantLA/reducers/collection';

const REPORTING_LOGS = 'REPORTING_LOGS';

const fetchAction = getFetchActionName(REPORTING_LOGS);
const loadMoreAction = REPORTING_LOGS + '_LOAD_MORE';
const pollLogAction = REPORTING_LOGS + '_POLLING';
const createAction = 'REPORTING_LOG' + '_CREATE';

const filterSameObjects = (state, action) => {
  const existingIds = state.items.map(({ id }) => id);
  const newObjects = action.payload.data.items.filter(
    ({ id }) => !existingIds.includes(id)
  );
  return {
    ...state,
    items: [...newObjects, ...state.items],
  };
};

const handleFetchLogsPending = (state, action, initialState) =>
  state.items.length > 0 ? { ...state } : { ...initialState };

const handleFetchLogsSuccess = (state, action) => {
  const firstFetch = state.items.length < 1;

  return firstFetch
    ? listFetchSuccessState(state, action)
    : filterSameObjects(state, action);
};

const handleLoadMoreLogsSuccess = (state, action) => ({
  ...state,
  items: [...state.items, ...action.payload.data.items],
});

const appendEntityIfNotDuplicated = (state, action) =>
  action.payload.is_already_present
    ? { ...state }
    : appendEntityToList(state, action);

// Reporting log reducer
export const fetchLogs = params => fetchAll(params, Log, REPORTING_LOGS);

export const logListReducer = makeActionCollectionReducer(REPORTING_LOGS, {
  [`${fetchAction}::PENDING`]: handleFetchLogsPending,
  [`${fetchAction}::SUCCESS`]: handleFetchLogsSuccess,
  [`${loadMoreAction}::SUCCESS`]: handleLoadMoreLogsSuccess,
  [`${pollLogAction}::SUCCESS`]: updateEntityInList,
  [`${createAction}::SUCCESS`]: appendEntityIfNotDuplicated,
});

// Actions
export const loadMore = params => ({
  type: fetchAction,
  payload: new Log().fetchAll(params),
});

export const createLog = payload => ({
  type: createAction,
  payload: new Log().save(payload).then(data => ({
    ...data,
    isNew: true,
  })),
});

export const pollLog = logId => ({
  type: pollLogAction,
  payload: new Log().poll(logId),
});
