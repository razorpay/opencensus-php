import React from 'react';
import { Amount } from '@razorpay/blade/components';

type AverageBillingCellProps = { averageBilling: number };

const AverageBillingCell = ({ averageBilling }: AverageBillingCellProps) => {
  return (
    <Amount
      type="body"
      size="medium"
      weight="semibold"
      isAffixSubtle
      suffix="decimals"
      value={averageBilling}
    />
  );
};

export default AverageBillingCell;
