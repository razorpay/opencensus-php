import { ArrowRightIcon, Box, Button, Divider, Text } from '@razorpay/blade/components';
import React from 'react';
import { DEVICE_DEPLOYMENT_FIELDS } from 'apps/pos/src/app/types/DeviceDeployment';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import {
  WD10_WIFI_CONFIG_INSTRUCTIONS,
  WD10_WIFI_CONFIG_URL,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/constants';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';

interface WifiInstructionsProps {
  onWifiConfigSuccess: () => void;
}

const WifiInstructions = ({ onWifiConfigSuccess }: WifiInstructionsProps): JSX.Element => {
  const { states, handlers } = useOnboardingContext();
  const { isUpdateModularLoading } = states;
  const { updateModularConfig } = handlers;
  const { isMobile } = useScreen();

  const handleConfigureDeviceWifi = () => {
    window.open(WD10_WIFI_CONFIG_URL, '_blank');
  };

  const handleWifiConfigurationSuccessful = () => {
    const payload = {
      [DEVICE_DEPLOYMENT_FIELDS.DEVICE_WIFI_CONFIGURATION_STATUS_FIELD]: true,
      [DEVICE_DEPLOYMENT_FIELDS.MODULAR_CALLBACK]: onWifiConfigSuccess,
    };
    updateModularConfig(payload);
  };

  return (
    <Box padding="spacing.0">
      <Box>
        <Text
          size="large"
          weight="semibold"
          color="surface.text.gray.normal"
          marginBottom="spacing.6"
        >
          Wifi Setup Instructions
        </Text>
      </Box>
      <Divider marginBottom="spacing.6" />
      <Box padding={['spacing.0', 'spacing.6']}>
        {WD10_WIFI_CONFIG_INSTRUCTIONS.map((instruction) => {
          return (
            <Box key={instruction.id} display="flex" marginBottom="spacing.7" gap="spacing.2">
              <Text>{instruction.id}.</Text>
              <Text>{instruction.title}</Text>
            </Box>
          );
        })}
      </Box>
      <Divider />
      <Button
        variant="primary"
        color="primary"
        marginTop="spacing.6"
        icon={ArrowRightIcon}
        onClick={handleConfigureDeviceWifi}
        size={isMobile ? 'medium' : 'large'}
        isFullWidth
        iconPosition="right"
      >
        Configure Device Wifi
      </Button>
      <Button
        marginTop="spacing.6"
        variant="secondary"
        color="primary"
        onClick={handleWifiConfigurationSuccessful}
        size={isMobile ? 'medium' : 'large'}
        isFullWidth
        iconPosition="right"
        isLoading={isUpdateModularLoading}
      >
        Wifi-configuration successful
      </Button>
    </Box>
  );
};

export default WifiInstructions;
