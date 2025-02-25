import React from 'react';
import PaymentMethodContextProvider from './PaymentMethodContextProvider';
import PaymentMethodForm from './PaymentMethodForm';
import NACHForm from './NACHForm';
import { Box } from '@razorpay/blade/components';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';
import BrandEMIFormContainer from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/BrandEMIFormContainer';
import AddedBrandInfoContainer from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/PaymentMethods/BrandEMIForm/AddedBrandInfoContainer';

const PaymentMethods = ({ nach = false, brandEmi = false, addedBrands = false }) => {
  const getComponent = () => {
    if (brandEmi) return BrandEMIFormContainer;
    if (addedBrands) return AddedBrandInfoContainer;
    if (nach) return NACHForm;
    return PaymentMethodForm;
  };
  return (
    <ErrorBoundary
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
      <PaymentMethodContextProvider
        component={getComponent()}
        nach={nach}
        brandEmi={brandEmi}
        addedBrands={addedBrands}
      />
    </ErrorBoundary>
  );
};

export default PaymentMethods;
