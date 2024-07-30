import React from 'react';
import useOnboardingContext from '../../../providers';
import DevicePayment from './DevicePayment';
import { getDevicePaymentFields } from 'apps/pos/src/app/utils/deviceSelection';

const DevicePaymentForPosSalesAgent = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading, merchantDetails } = states;
  const { updateModularConfig, handleProceedToNextComponent } = handlers;

  if (!modularConfig || !merchantDetails) return null;

  const { qrImageContent, qrTotalAmount, qrPaymentStatus } = getDevicePaymentFields({
    modularConfig,
  });

  return (
    <DevicePayment
      merchantName={merchantDetails?.contactPerson?.name?.value ?? ''}
      qrCodeIntent={qrImageContent}
      amount={Number(qrTotalAmount ?? 0)}
      handleGoToNextStep={handleProceedToNextComponent}
      handleModularUpdate={updateModularConfig}
      isPaymentSuccessfull={qrPaymentStatus === 'success'}
      isUpdateModularLoading={isUpdateModularLoading}
    />
  );
};

export default DevicePaymentForPosSalesAgent;
