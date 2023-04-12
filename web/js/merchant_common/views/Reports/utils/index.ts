import storeForMerchantPartnerDashboard from 'merchant/store';
import storeForLinkedAccountDashboard from 'merchantLA/store';
import { connect } from 'react-redux';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { Store } from 'common/typings';

export const getReportStore = (dashboardType: DashboardType) => {
  switch (dashboardType) {
    case 'merchant':
      return storeForMerchantPartnerDashboard;
    case 'partner':
      return storeForMerchantPartnerDashboard;
    case 'linkedAccount':
      return storeForLinkedAccountDashboard;
    default:
      return null;
  }
};

export const withReportsSplitzExperiment = (Component) =>
  connect(({ session: { user } }: Store) => {
    return {
      revampedMerchantReports: user?.isRevampedReportsEnabled?.merchant,
      revampedPartnerReports: user?.isRevampedReportsEnabled?.partner,
      revampedLAReports: user?.isRevampedReportsEnabled?.la,
    };
  }, null)(Component);
