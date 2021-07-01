import React, { useEffect } from 'react';
import AmountWithdrawn from './AmountWithdraw';
import OverviewFooter from './OverviewFooter/index';
import { trackOverviewTab } from './TrackEvents/trackEvents';

const Overview = () => {
  useEffect(() => {
    trackOverviewTab();
  }, []);

  return (
    <div className="cash-advance-overview">
      <AmountWithdrawn />
      <OverviewFooter />
    </div>
  );
};

export default Overview;
