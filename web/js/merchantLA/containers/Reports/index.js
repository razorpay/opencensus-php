import Reportsx from 'merchant_common/containers/Reports';
import EmailReportx from 'merchant_common/components/Reports/EmailReport';
import store from 'merchantLA/store';
import * as data from 'merchantLA/containers/Reports/data';
import * as modelActions from 'merchantLA/modules/reports';
import * as ga from './ga';

const Reports = Reportsx(store, { data, modelActions, ga });

export const EmailReport = EmailReportx({
  emailReportV2: modelActions.emailReportV2,
  marketplaceConfigTypes: data.marketplaceConfigTypes,
  ga,
});

export default Reports;
