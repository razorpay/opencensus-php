import React from 'react';
import { PaymentPagesProductsStatusLabel } from 'merchant/components/StatusLabel';

const getStatus = (status) => {
  if (status === 'unlimited' || status === 'in_stock') {
    return 'available';
  }

  return 'out_of_stock';
};

const CatalogStatusLabel = ({ status, ...rest }) => {
  return <PaymentPagesProductsStatusLabel status={getStatus(status)} {...rest} />;
};

export default React.memo(CatalogStatusLabel);
