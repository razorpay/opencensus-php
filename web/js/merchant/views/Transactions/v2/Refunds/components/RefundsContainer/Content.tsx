import React from 'react';
import Ftux from 'merchant/views/Transactions/v2/common/components/Ftux';
import { Spinner, Box } from '@razorpay/blade/components';
import { ContentProps } from './types';
import RefundsList from 'merchant/views/Transactions/v2/Refunds/components/RefundsList';
import { Page, View } from 'merchant/views/Transactions/v2/common/types';

const { LOADING, FTUX, FAILED_FTUX, LIST } = View;

const Content = ({ view }: ContentProps): JSX.Element => {
  switch (view) {
    case LOADING:
      return (
        <Box display="flex" alignItems="center" justifyContent="center" height="360px">
          <Spinner accessibilityLabel="Loading refunds" />
        </Box>
      );
    case FTUX:
      return <Ftux page={Page.REFUNDS} />;
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
          Unable to fetch refunds information at this moment, please try again later.
        </Box>
      );
    case LIST:
    default:
      return <RefundsList />;
  }
};

export default Content;
