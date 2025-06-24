import React, { useEffect } from 'react';
import ShellLayout from '../ShellLayout';
import { ProductRouter } from '../ProductRouter';
import { initOneDashboardAnalytics } from '../../utils/analytics';
import { SyncProductStore } from '../SyncProductStore';
import { migrateGlobalModeTokensToMerchantModeTokens } from '@libs/shared-utils';

const RazorpayDashboard = () => {
  useEffect(() => {
    initOneDashboardAnalytics();
    migrateGlobalModeTokensToMerchantModeTokens();
  }, []);

  return (
    <ShellLayout>
      <SyncProductStore />
      <ProductRouter />
    </ShellLayout>
  );
};
export default RazorpayDashboard;
