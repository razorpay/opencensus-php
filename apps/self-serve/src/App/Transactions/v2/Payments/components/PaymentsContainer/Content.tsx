import React from 'react';
import { Spinner, Box } from '@razorpay/blade/components';

import PaymentsList from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsList';
import Ftux from 'apps/self-serve/src/App/Transactions/v2/common/components/Ftux';
import { Page, View } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import { ContentProps } from './types';

const { LOADING, FTUX, FAILED_FTUX, LIST } = View;
const { FAILED_PAYMENTS, PAYMENTS } = Page;

const Content = ({ view, shouldShowFailedPayments }: ContentProps): JSX.Element => {
  switch (view) {
    case LOADING:
      return (
        <Box display="flex" alignItems="center" justifyContent="center" height="360px">
          <Spinner accessibilityLabel="Loading payments" />
        </Box>
      );
    case FTUX: {
      const page = shouldShowFailedPayments ? FAILED_PAYMENTS : PAYMENTS;
      return <Ftux page={page} />;
    }
    case FAILED_FTUX:
      return (
        <Box
          display="flex"
          alignItems={{
            l: 'center',
          }}
          justifyContent="center"
          height="360px"
        >
          Unable to fetch payments information at this moment, please try again later.
        </Box>
      );
    case LIST:
    default:
      return <PaymentsList />;
  }
};

export default Content;
