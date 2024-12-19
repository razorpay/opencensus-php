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
const pollLogAction = REPORTING_LOGS + '_POLLING';
const createAction = 'REPORTING_LOG' + '_CREATE';

const handleFetchLogsPending = (state) => ({
  ...state,
  error: null,
  pending: true,
  items: state.items,
});

const handleFetchLogsSuccess = (state, action) => {
  // This is done to prevent redundant entries in the items array, ensuring the state remains efficient and accurate.
  const newItems = action.payload.data.items.filter(
    (item) => !state.items.some((existingItem) => existingItem.id === item.id),
  );

  return {
    ...state,
    pending: false,
    items: [...state.items, ...newItems],
    allFetched: action.payload.data.count < 5,
  };
};

const appendEntityIfNotDuplicated = (state, action) =>
  action.payload.is_already_present ? { ...state } : appendEntityToList(state, action);

// Reporting log reducer
export const fetchLogs = (params) => fetchAll(params, Log, REPORTING_LOGS);

export const logListReducer = makeActionCollectionReducer(REPORTING_LOGS, {
  [`${fetchAction}::PENDING`]: handleFetchLogsPending,
  [`${fetchAction}::SUCCESS`]: handleFetchLogsSuccess,
  [`${pollLogAction}::SUCCESS`]: updateEntityInList,
  [`${createAction}::SUCCESS`]: appendEntityIfNotDuplicated,
});

// Actions

export const createLog = (payload) => ({
  type: createAction,
  payload: new Log().save(payload).then((data) => ({
    ...data,
    isNew: true,
  })),
});

export const pollLog = (logId) => ({
  type: pollLogAction,
  payload: new Log().poll(logId),
});
