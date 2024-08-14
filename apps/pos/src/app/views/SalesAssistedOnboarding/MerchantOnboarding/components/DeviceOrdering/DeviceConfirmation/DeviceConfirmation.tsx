import React, { useEffect, useState } from 'react';
import { Alert, Box, Divider, Heading } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import DeviceOrderSummaryItem from './DeviceOrderSummaryItem';
import DeviceConfirmationCTA from './DeviceConfirmationCTA';
import DeviceConfirmationCustomPricing from './DeviceConfirmationCustomPricing';
import { AvailableComponents, AvailableSteps } from 'apps/pos/src/app/types/common';
import {
  ArrayOfDocumentFieldsUpload,
  DeviceCharges,
  ModularPayload,
} from 'apps/pos/src/app/types/modular';
import { MODULAR_FLAGS } from 'apps/pos/src/app/constants/DeviceSelection';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import {
  MODULAR_DEVICE_FIELDS,
  OrderSummaryItemWithDeviceConfig,
} from 'apps/pos/src/app/types/DeviceSelection';

interface DeviceConfirmationProps {
  addedDevices: OrderSummaryItemWithDeviceConfig[];
  orderSummary: DeviceCharges;
  customPricingDocuments: ArrayOfDocumentFieldsUpload[];
  title: string;
  merchantId: string;
  isUpdateModularLoading: boolean;
  isDisabled: boolean;
  isCustomRatesApplicable: boolean;
  handleUpdateModular: (data: ModularPayload) => void;
  handleGoToNextStep: () => void;
}

const DeviceConfirmation = ({
  addedDevices,
  orderSummary,
  customPricingDocuments,
  title,
  merchantId,
  isUpdateModularLoading,
  isDisabled,
  isCustomRatesApplicable,
  handleUpdateModular,
  handleGoToNextStep,
}: DeviceConfirmationProps): JSX.Element | null => {
  const navigate = useNavigate();
  const [error, setError] = useState<string>('');

  useEffect(() => {
    setError('');
  }, [addedDevices]);

  const handleNavigateToCatalog = () => {
    navigate(
      `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${merchantId}/${AvailableSteps.DEVICE_SELECTION}/${AvailableComponents.DEVICE_SELECTION_CATALOG}`,
    );
  };

  const handleOrderConfirmation = (): void => {
    if (isCustomRatesApplicable && customPricingDocuments.length === 0) {
      setError('Please upload custom pricing proof to proceed');
      return;
    }

    const payload = {
      ...MODULAR_FLAGS.CONFIRM_ORDER,
      [MODULAR_DEVICE_FIELDS.MODULAR_CALLBACK]: handleGoToNextStep,
    };
    handleUpdateModular(payload);
  };

  return (
    <Box margin="spacing.5">
      <Heading marginBottom="spacing.5" size="large">
        {title}
      </Heading>
      {addedDevices?.length === 0 ? (
        <Alert
          title="No Devices added"
          description="Please add devices from the device selection page to proceed"
          color="notice"
          actions={{ primary: { text: 'Add Devices', onClick: handleNavigateToCatalog } }}
          isDismissible={false}
          isFullWidth
        />
      ) : (
        <Box marginBottom="100px">
          {isCustomRatesApplicable ? (
            <DeviceConfirmationCustomPricing
              defaultValues={customPricingDocuments ?? []}
              handleModularUpdate={handleUpdateModular}
              isDisabled={isDisabled}
              error={error}
            />
          ) : null}
          {addedDevices?.map((device, index) => (
            <React.Fragment key={device.itemId}>
              <DeviceOrderSummaryItem
                device={device}
                deviceConfig={device.deviceConfig}
                isUpdateModularLoading={isUpdateModularLoading}
                isDisabled={isDisabled}
                handleUpdateModular={handleUpdateModular}
              />
              {index !== addedDevices.length - 1 ? (
                <Divider orientation="horizontal" margin="spacing.3" />
              ) : null}
            </React.Fragment>
          ))}
        </Box>
      )}

      <DeviceConfirmationCTA
        ctaName="Confirm Order"
        onCtaClick={handleOrderConfirmation}
        isLoading={isUpdateModularLoading}
        addedDevices={addedDevices ?? []}
        orderSummary={orderSummary ?? {}}
        isDisabled={(addedDevices ?? []).length === 0 || isDisabled}
      />
    </Box>
  );
};

export default DeviceConfirmation;
