import React from 'react';
import { Amount } from '@razorpay/blade/components';

type TotalSalesCellProps = { totalSales: number };

const TotalSalesCell = ({ totalSales }: TotalSalesCellProps) => {
  return (
    <Amount
      type="body"
      size="medium"
      weight="semibold"
      isAffixSubtle
      suffix="decimals"
      value={totalSales}
    />
  );
};

export default TotalSalesCell;
