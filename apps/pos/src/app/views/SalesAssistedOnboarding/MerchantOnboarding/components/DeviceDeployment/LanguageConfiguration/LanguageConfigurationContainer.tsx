import React from 'react';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { Box } from '@razorpay/blade/components';
import { getLanguageDetailsFromModularConfig } from 'apps/pos/src/app/utils/deviceDeployment';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import LanguageConfiguration from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/LanguageConfiguration/LanguageConfiguration';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

const LanguageConfigurationContainer = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { updateModularConfig } = handlers;
  if (!modularConfig) return null;

  const languageDetails = getLanguageDetailsFromModularConfig({ modularConfig });
  if (!languageDetails) return null;

  const { deviceName, languageList, description = '' } = languageDetails;

  return (
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.DEVICE_DEPLOYMENT }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <LanguageConfiguration
        languageList={languageList}
        deviceName={deviceName}
        description={description}
        isUpdateModularLoading={isUpdateModularLoading}
        handleUpdateModularConfig={updateModularConfig}
      />
    </ErrorBoundary>
  );
};

export default LanguageConfigurationContainer;
