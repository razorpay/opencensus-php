import Log from 'merchant/models/Log';
import {
  makeActionCollectionReducer,
  fetchAll,
} from 'merchant/reducers/collection';

const LOGS = 'LOGS';

export const fetchLogs = params => fetchAll(params, Log, LOGS);
export const logListReducer = makeActionCollectionReducer(LOGS);
