import React from 'react';
import PaymentMethodContextProvider from './PaymentMethodContextProvider';
import PaymentMethodForm from './PaymentMethodForm';
import NACHForm from './NACHForm';
import { Box } from '@razorpay/blade/components';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import errorService from '@razorpay/universe-cli/errorService';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

const PaymentMethods = ({ nach = false }) => {
  return (
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.PAYMENT_METHODS }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <PaymentMethodContextProvider component={nach ? NACHForm : PaymentMethodForm} nach={nach} />
    </ErrorBoundary>
  );
};

export default PaymentMethods;
