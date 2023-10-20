import React from 'react';

import PaymentDownloadSwiftCopy from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/DownloadSwiftCopy';

import type { DownloadSwiftCopy } from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/types';

const downloadSwiftCopy: DownloadSwiftCopy = {
  title: 'Action',
  value: (item) => {
    return <PaymentDownloadSwiftCopy paymentId={item.id} asIcon />;
  },
};

export default downloadSwiftCopy;
