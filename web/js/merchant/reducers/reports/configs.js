import Config from 'merchant/models/Reports/Config';

import {
  makeActionCollectionReducer,
  fetchAll,
} from 'merchant/reducers/collection';

const CONFIGS = 'CONFIGS';

export const fetchConfigs = params => fetchAll(params, Config, CONFIGS);
export const configListReducer = makeActionCollectionReducer(CONFIGS);
