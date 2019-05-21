import Reportsx from 'merchant_common/containers/Reports';
import EmailReportx from 'merchant_common/components/Reports/EmailReport';
import store from 'merchant/store';
import * as data from 'merchant/containers/Reports/data';
import * as modelActions from 'merchant/modules/reports';
import { fetchAccountsApi } from 'merchant/modules/marketplace/accounts';
import * as ga from './ga';

const Reports = Reportsx(store, {
  data,
  modelActions,
  fetchAccountsApi,
  ga,
  shouldFetchPartnerConfigs: true,
  linkToReports: '/partners/reports',
});

export const EmailReport = EmailReportx({
  emailReportV2: modelActions.emailReportV2,
  marketplaceConfigTypes: data.marketplaceConfigTypes,
  ga,
});

export default Reports;
