import React from 'react';
import { Box } from '@razorpay/blade/components';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { PosAgreementMode } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/PosAgreementMode';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

const AgreementSigning = () => {
  const { states, handlers } = useOnboardingContext();

  const { modularConfig, isModularLoading, isUpdateModularLoading, merchantDetails } = states;
  const { updateModularConfig } = handlers;

  if (!modularConfig) return null;

  return (
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.AGREEMENT_SIGNING }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <PosAgreementMode
        modularConfig={modularConfig}
        isModularLoading={isModularLoading}
        isUpdateModularLoading={isUpdateModularLoading}
        updateModularConfig={updateModularConfig}
        merchantDetails={merchantDetails}
      />
    </ErrorBoundary>
  );
};
export default AgreementSigning;
