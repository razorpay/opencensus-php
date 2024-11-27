import { Box, Button, Text, TextInput } from '@razorpay/blade/components';
import React, { useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import { DEVICE_DEPLOYMENT_FIELDS } from 'apps/pos/src/app/types/DeviceDeployment';
import { getDeviceMappingDetailsFromModularConfig } from 'apps/pos/src/app/utils/deviceDeployment';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import { ScannerType } from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/DeviceMapping/DeviceMappingScannerComponent/DeviceMappingScannerContainer';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

const DeviceMappingManualComponent = (): JSX.Element | null => {
  const [serialNumber, setSerialNumber] = useState('');
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { updateModularConfig } = handlers;
  const { id, step } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const deviceId = queryParams.get('deviceId') || '';

  if (!modularConfig) return null;

  const deviceMappingDetails = getDeviceMappingDetailsFromModularConfig({ modularConfig });

  const handleSerialNumberChange = ({ value = '' }: { value?: string }) => {
    setSerialNumber(value);
  };

  const handleProceedToQRScan = () => {
    navigate(
      `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${AvailableComponents.DEVICE_MAPPING_SCANNER}?deviceId=${deviceId}&scannerType=${ScannerType.QR_CODE}`,
    );
  };

  const handleConfirmSerialNumber = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Confirm Serial Number',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_DEPLOYMENT,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.EXPLORE_DEVICE_DEPLOYMENT,
        section: 'Device Serial Number',
        subSection: 'Device Serial Number',
      },
    });
    const payload = {
      [DEVICE_DEPLOYMENT_FIELDS.CURRENT_DEVICE_ID_FIELD]: deviceId,
      [DEVICE_DEPLOYMENT_FIELDS.DEVICE_SERIAL_NUMBER_FIELD]: serialNumber,
      [DEVICE_DEPLOYMENT_FIELDS.MODULAR_CALLBACK]: handleProceedToQRScan,
    };
    updateModularConfig(payload);
  };

  return (
    <Box padding="spacing.7" display="flex" flexDirection="column" gap="spacing.6">
      <Text size="large" weight="semibold">
        Enter Device Details Manually
      </Text>
      <Box display="flex" justifyContent="space-between">
        <Text size="medium" color="surface.text.gray.subtle">
          Model Details
        </Text>
        <Text size="medium" color="surface.text.gray.subtle">
          {deviceMappingDetails?.details_page_name}
        </Text>
      </Box>
      <TextInput
        value={serialNumber}
        onChange={handleSerialNumberChange}
        placeholder=""
        label="Device Serial Number"
        size="medium"
      />
      <Box
        display="flex"
        justifyContent="center"
        position="fixed"
        bottom="0px"
        padding="spacing.4"
        backgroundColor="surface.background.gray.intense"
        left="0px"
        right="0px"
        zIndex="1"
      >
        <Button onClick={handleConfirmSerialNumber} isFullWidth isLoading={isUpdateModularLoading}>
          Confirm Serial Number
        </Button>
      </Box>
    </Box>
  );
};

export default DeviceMappingManualComponent;
