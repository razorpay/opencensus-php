import React from 'react';

import AmountWithdrawn from './AmountWithdraw';
import OverviewFooter from './OverviewFooter/index';

const Overview = () => {
  return (
    <div className="cash-advance-overview">
      <AmountWithdrawn />
      <OverviewFooter />
    </div>
  );
};

export default Overview;
