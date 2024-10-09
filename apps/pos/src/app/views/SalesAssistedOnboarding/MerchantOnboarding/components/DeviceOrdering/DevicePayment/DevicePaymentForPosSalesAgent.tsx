import React from 'react';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { Box } from '@razorpay/blade/components';
import useOnboardingContext from '../../../providers';
import DevicePayment from './DevicePayment';
import { getDevicePaymentFields } from 'apps/pos/src/app/utils/deviceSelection';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

const DevicePaymentForPosSalesAgent = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading, merchantDetails } = states;
  const { updateModularConfig, handleProceedToNextComponent } = handlers;

  if (!modularConfig || !merchantDetails) return null;

  const { qrImageContent, qrTotalAmount, qrPaymentStatus } = getDevicePaymentFields({
    modularConfig,
  });

  return (
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.DEVICE_PAYMENT }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <DevicePayment
        merchantName={merchantDetails?.contactPerson?.name?.value ?? ''}
        qrCodeIntent={qrImageContent}
        amount={Number(qrTotalAmount ?? 0)}
        handleGoToNextStep={handleProceedToNextComponent}
        handleModularUpdate={updateModularConfig}
        isPaymentSuccessfull={qrPaymentStatus === 'success'}
        isUpdateModularLoading={isUpdateModularLoading}
      />
    </ErrorBoundary>
  );
};

export default DevicePaymentForPosSalesAgent;
