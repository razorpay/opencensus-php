import React, { useEffect } from 'react';
import ShellLayout from '../ShellLayout';
import { ProductRouter } from '../ProductRouter';
import { initOneDashboardAnalytics } from '../../utils/analytics';
import { SyncProductStore } from '../SyncProductStore';

const RazorpayDashboard = () => {
  useEffect(() => {
    initOneDashboardAnalytics();
  }, []);

  return (
    <ShellLayout>
      <SyncProductStore />
      <ProductRouter />
    </ShellLayout>
  );
};
export default RazorpayDashboard;
