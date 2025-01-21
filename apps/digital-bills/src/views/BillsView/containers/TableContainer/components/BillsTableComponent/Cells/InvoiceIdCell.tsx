import React from 'react';
import { Link } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

type InvoiceIdCellProps = {
  billId: string;
  legacyEntityId: string;
};

const InvoiceIdCell = ({ billId, legacyEntityId }: InvoiceIdCellProps): React.ReactElement => {
  const navigate = useNavigate();
  return (
    <Link onClick={() => navigate(`${billId}?legacyEntityId=${legacyEntityId}`)} size="medium">
      {billId}
    </Link>
  );
};

export default InvoiceIdCell;
