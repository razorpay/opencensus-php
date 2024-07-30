import React from 'react';
import DeviceQR from './DeviceQR';
import DevicePaymentSuccess from './DevicePaymentSuccess';
import { ModularPayload } from 'apps/pos/src/app/types/modular';
import PageError from 'apps/pos/src/app/components/PageError';

interface DevicePaymentProps {
  qrCodeIntent: string;
  merchantName: string;
  amount: number;
  isUpdateModularLoading: boolean;
  isPaymentSuccessfull: boolean;
  handleGoToNextStep: () => void;
  handleModularUpdate: (payload: ModularPayload) => void;
}

const DevicePayment = ({
  qrCodeIntent,
  merchantName,
  amount,
  isUpdateModularLoading,
  isPaymentSuccessfull,
  handleGoToNextStep,
  handleModularUpdate,
}: DevicePaymentProps): JSX.Element => {
  if (!qrCodeIntent) return <PageError description="QR code intent not found" />;

  return isPaymentSuccessfull ? (
    <DevicePaymentSuccess handleGoToNextStep={handleGoToNextStep} />
  ) : (
    <DeviceQR
      qrCodeIntent={qrCodeIntent}
      merchantName={merchantName}
      amount={amount}
      isUpdateModularLoading={isUpdateModularLoading}
      handleModularUpdate={handleModularUpdate}
    />
  );
};

export default DevicePayment;
