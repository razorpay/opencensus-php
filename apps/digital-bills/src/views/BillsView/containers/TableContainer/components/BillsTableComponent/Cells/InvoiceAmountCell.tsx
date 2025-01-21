import React from 'react';
import { Amount } from '@razorpay/blade/components';

type InvoiceAmountCellProps = { amount: number };

const InvoiceAmountCell = ({ amount }: InvoiceAmountCellProps): React.ReactElement => {
  return amount >= 0 ? <Amount value={amount} /> : <>-</>;
};

export default InvoiceAmountCell;
