import Reportsx from 'merchant_common/containers/Reports';
import store from 'merchant/store';
import * as data from 'merchant/views/Reports/data';
import * as modelActions from 'merchant/reducers/reports';
import { fetchAccountsApi } from 'merchant/reducers/marketplace/accounts';
import * as ga from './ga';

const Reports = Reportsx(store, { data, modelActions, fetchAccountsApi, ga });

export default Reports;
