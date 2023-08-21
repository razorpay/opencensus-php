import storeForMerchantPartnerDashboard from 'merchant/store';
import storeForLinkedAccountDashboard from 'merchantLA/store';
import { DashboardType } from 'merchant_common/views/Reports/types';

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
