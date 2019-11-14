import Reportsx from 'merchant_common/containers/Reports';
import store from 'merchant/store';
import * as datax from 'merchant/containers/Reports/data';
import * as modelActions from 'merchant/reducers/reports';
import { fetchAccountsApi } from 'merchant/reducers/marketplace/accounts';
import * as ga from './ga';

// TODO: need better method for an empty custom configs
// to avoid monthly invoice in partner reports tab
const data = {
  ...datax,
  getCustomConfig: () => null,
};

const Reports = Reportsx(store, {
  data,
  modelActions,
  fetchAccountsApi,
  ga,
  isPartnerReport: true,
});

export default Reports;
