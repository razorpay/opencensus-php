import React, { useContext } from 'react';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { Box } from '@razorpay/blade/components';
import useOnboardingContext from '../../../providers';
import DevicePayment from './DevicePayment';
import { getDevicePaymentFields } from 'apps/pos/src/app/utils/deviceSelection';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';
import { SpiltzContext } from 'shell/SpiltzServiceContext';

const DevicePaymentForPosSalesAgent = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const splitz = useContext(SpiltzContext);
  const { modularConfig, isUpdateModularLoading, merchantDetails, isPosEkycAgent } = states;
  const { updateModularConfig, handleProceedToNextComponent } = handlers;
  // if expt below is ON, then BE will send payment_options_component on address confirmation. Else, BE will continue sending qr_code_component
  const isBackendPLExptOn =
    splitz.abExperiments?.pos_payment_link_qr_comp?.variables?.result === 'on';
  if (!modularConfig || !merchantDetails) return null;

  const { qrImageContent, qrTotalAmount, qrPaymentStatus } = getDevicePaymentFields({
    modularConfig,
    isPosEkycAgent,
    isBackendPLExptOn,
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
