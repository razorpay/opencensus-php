import React from 'react';
import { Box, Link } from '@razorpay/blade/components';

import { getCustomURL } from 'merchant/components/DocsLink';

const ConfigFooter = () => {
  return (
    <Box paddingY="spacing.5">
      Changes will reflect on{' '}
      <Link
        target="_blank"
        rel="noopener noreferrer"
        href={getCustomURL('https://razorpay.com/payment-gateway/')}
      >
        Checkout page
      </Link>
      ,{' '}
      <Link
        target="_blank"
        rel="noopener noreferrer"
        href={getCustomURL('https://razorpay.com/payment-links/')}
      >
        Payment Links
      </Link>
      ,{' '}
      <Link
        target="_blank"
        rel="noopener noreferrer"
        href={getCustomURL('https://razorpay.com/invoices/')}
      >
        Invoices
      </Link>{' '}
      &{' '}
      <Link
        target="_blank"
        rel="noopener noreferrer"
        href={getCustomURL('https://razorpay.com/payment-pages')}
      >
        Payment pages
      </Link>
      {''}.
    </Box>
  );
};

export default ConfigFooter;
