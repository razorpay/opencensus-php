import { Box } from '@razorpay/blade/components';
import React, { useState } from 'react';
import { useLocation } from 'react-router-dom';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { getDeviceMappingDetailsFromModularConfig } from 'apps/pos/src/app/utils/deviceDeployment';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import DeviceMappingScannerComponent from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/DeviceMapping/DeviceMappingScannerComponent/DeviceMappingScannerComponent';
import { DeviceModel } from 'apps/pos/src/app/utils/deviceSelection';
import PageError from 'apps/pos/src/app/components/PageError';
import { MODULES } from 'apps/pos/src/app/types/common';

export enum ScannerType {
  BAR_CODE = 'barCode',
  QR_CODE = 'qrCode',
}

const DeviceMappingScannerContainer = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { updateModularConfig } = handlers;

  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const scannerType = queryParams.get('scannerType');
  const validScannerType =
    scannerType === ScannerType.BAR_CODE || scannerType === ScannerType.QR_CODE
      ? (scannerType as ScannerType)
      : ScannerType.BAR_CODE;
  const [activeScanner, setActiveScanner] = useState<ScannerType>(validScannerType);
  const isBarCodeActive = activeScanner === ScannerType.BAR_CODE;

  if (!modularConfig) return null;

  const deviceDetails = getDeviceMappingDetailsFromModularConfig({ modularConfig });
  const disableBarcode = deviceDetails?.details_page_name === DeviceModel.STICKER_AND_STANDEE;

  return (
    <ErrorBoundary
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
      <DeviceMappingScannerComponent
        handleModularUpdate={updateModularConfig}
        isBarCodeActive={isBarCodeActive}
        setActiveScanner={setActiveScanner}
        isLoading={isUpdateModularLoading}
        disableBarcode={disableBarcode}
      />
    </ErrorBoundary>
  );
};

export default DeviceMappingScannerContainer;
