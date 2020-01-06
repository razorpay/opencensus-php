import Config from 'merchant/models/Reports/Config';

import {
  makeActionCollectionReducer,
  fetchAll,
} from 'merchant/reducers/collection';

const PARTNER_CONFIGS = 'PARTNER_CONFIGS';
const MERCHANT_CONFIGS = 'MERCHANT_CONFIGS';

export const fetchMerchantConfigs = params =>
  fetchAll(params, Config, MERCHANT_CONFIGS);
export const merchantConfigListReducer = makeActionCollectionReducer(
  MERCHANT_CONFIGS
);

export const fetchPartnerConfigs = params =>
  fetchAll(params, Config, PARTNER_CONFIGS);
export const partnerConfigListReducer = makeActionCollectionReducer(
  PARTNER_CONFIGS
);
