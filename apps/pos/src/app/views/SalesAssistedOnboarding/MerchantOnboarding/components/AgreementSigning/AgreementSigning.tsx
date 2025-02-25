import React, { useEffect } from 'react';
import { Box } from '@razorpay/blade/components';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { PosAgreementMode } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/PosAgreementMode';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

const AgreementSigning = () => {
  const { states, handlers } = useOnboardingContext();

  const { modularConfig, isModularLoading, isUpdateModularLoading, merchantDetails } = states;
  const { updateModularConfig } = handlers;

  useEffect(() => {
    if (modularConfig) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_PAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          formName: 'Agreement Signing',
          section: 'Agreement Signing',
          subSection: 'Signing Mode',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.SIGNING_MODE,
        },
      });
    }
  }, []);

  if (!modularConfig) return null;

  return (
    <ErrorBoundary
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
