import Reportsx from 'merchant_common/containers/Reports';
import store from 'merchantLA/store';
import * as data from 'merchantLA/containers/Reports/data';
import * as modelActions from 'merchantLA/modules/reports';
import * as ga from './ga';

const Reports = Reportsx(store, { data, modelActions, ga });

export default Reports;
