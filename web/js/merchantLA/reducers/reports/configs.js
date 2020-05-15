import Config from 'merchantLA/models/Reports/Config';

import {
  makeActionCollectionReducer,
  fetchAll,
} from 'merchantLA/reducers/collection';

const REPORTING_CONFIGS = 'REPORTING_CONFIGS';

export const fetchConfigs = params =>
  fetchAll(params, Config, REPORTING_CONFIGS);

export const configListReducer = makeActionCollectionReducer(REPORTING_CONFIGS);
