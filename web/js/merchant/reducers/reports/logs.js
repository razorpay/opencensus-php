import Log from 'merchant/models/Log';
import {
  makeActionCollectionReducer,
  fetchAll,
} from 'merchant/reducers/collection';

const LOGS = 'LOGS';

export const fetchLogs = params => fetchAll(params, Log, LOGS);
export const logListReducer = makeActionCollectionReducer(LOGS);

export const createLog = payload => ({
  type: 'LOG_CREATE',
  payload: new Log().save(payload),
});
