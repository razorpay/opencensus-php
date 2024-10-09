import {
  Alert,
  Badge,
  Box,
  Button,
  CheckCircleIcon,
  CloseIcon,
  Divider,
  IconButton,
  PosIcon,
  SettingsIcon,
  SpeakerIcon,
  Text,
  WifiIcon,
} from '@razorpay/blade/components';
import React, { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import DeviceConfigCard from './components/DeviceConfigCard';
import IconWrapper from './components/IconWrapper';
import WifiInstructions from './components/WifiInstructions';
import { IconContainer } from './styles';
import ModalWithBottomSheet from 'apps/pos/src/app/components/ModalWithBottomSheet';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import { ModularPayload } from 'apps/pos/src/app/types/modular';
import { MappingStatusType } from 'apps/pos/src/app/utils/deviceDeployment';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';

interface DeviceConfigurationProps {
  isUpdateModularLoading: boolean;
  deviceName: string;
  language: string;
  mappingStatus: MappingStatusType;
  hideLanguageSettings: boolean;
  hideWifiConfiguration: boolean;
  handleUpdateModularConfig: (payload: ModularPayload) => void;
  handleProceed: () => void;
}

const DeviceConfiguration = ({
  deviceName,
  mappingStatus,
  language,
  hideLanguageSettings,
  hideWifiConfiguration,
}: DeviceConfigurationProps): JSX.Element => {
  const [isSuccessModalOpen, setIsSuccessModalOpen] = useState<boolean>(false);
  const [isWifiConfigModalOpen, setIsWifiConfigModalOpen] = useState<boolean>(false);
  const { isMobile } = useScreen();
  const navigate = useNavigate();
  const { id, step } = useParams();
  const { isDeviceMapped, isDeviceTested, isWifiConfigured } = mappingStatus;
  const isAllDevicesDeployed = isDeviceMapped && isDeviceTested && isWifiConfigured;

  const handleDeployAnotherDevice = () => {
    if (isAllDevicesDeployed) {
      setIsSuccessModalOpen(true);
    } else {
      navigate(
        `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${AvailableComponents.DEVICE_DEPLOYMENT_LIST}`,
      );
    }
  };

  const handleConfigCard = (page: string) => {
    navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${page}`);
  };

  const handleViewAllDevices = () => {
    navigate(
      `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${AvailableComponents.DEVICE_DEPLOYMENT_LIST}`,
    );
  };

  const handleGoToHomepage = () => {
    navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}`);
  };

  const wifiConfigSuccessCallback = () => {
    setIsWifiConfigModalOpen(false);
  };

  return (
    <Box
      padding={['spacing.5', 'spacing.6']}
      backgroundColor="surface.background.gray.subtle"
      height="100vh"
    >
      <Box
        display="flex"
        alignItems="center"
        marginTop="spacing.6"
        gap="spacing.3"
        marginBottom="spacing.6"
      >
        <Text weight="semibold" size="medium">
          Device Details
        </Text>
        <Text weight="medium" size="small" color="surface.text.gray.muted">
          {deviceName}
        </Text>
      </Box>

      <Box
        padding={['spacing.5', 'spacing.6']}
        backgroundColor="surface.background.gray.intense"
        borderRadius="medium"
        marginBottom="spacing.6"
      >
        <Box
          display="flex"
          alignItems="center"
          justifyContent="space-between"
          marginBottom="spacing.6"
        >
          <IconWrapper Icon={PosIcon} />
          {isDeviceMapped ? (
            <Badge icon={CheckCircleIcon} color="positive">
              Done
            </Badge>
          ) : null}
        </Box>
        <Text weight="medium" size="medium" color="surface.text.gray.normal">
          Device Mapping
        </Text>
        <Text weight="regular" size="medium" color="surface.text.gray.subtle">
          Map device serial number and QR linked to the device
        </Text>
      </Box>
      <Button
        variant="primary"
        onClick={handleDeployAnotherDevice}
        size={isMobile ? 'medium' : 'large'}
        isFullWidth
        iconPosition="right"
        marginBottom="spacing.6"
      >
        {isAllDevicesDeployed ? 'Done' : 'Deploy Another Device'}
      </Button>

      {!hideLanguageSettings ? (
        <DeviceConfigCard
          onClick={() => handleConfigCard(AvailableComponents.LANGUAGE_CONFIGURATION)}
          title="Language settings"
          isDone={true}
          Icon={SettingsIcon}
          showOnlyText
          language={language}
        />
      ) : null}

      <DeviceConfigCard
        onClick={() => handleConfigCard(AvailableComponents.DEVICE_TESTING)}
        title="Device Testing"
        isDone={isDeviceTested}
        Icon={SpeakerIcon}
      />

      {!hideWifiConfiguration ? (
        <DeviceConfigCard
          onClick={() => setIsWifiConfigModalOpen(true)}
          title="Wifi-Configuration"
          isDone={isWifiConfigured}
          Icon={WifiIcon}
        />
      ) : null}

      <ModalWithBottomSheet
        content={<WifiInstructions onWifiConfigSuccess={wifiConfigSuccessCallback} />}
        isOpen={isWifiConfigModalOpen}
        headerText={''}
        onDismiss={() => setIsWifiConfigModalOpen(false)}
        snapPoints={[0.8, 0.8, 0.8]}
      />
      <ModalWithBottomSheet
        content={
          <Box position="relative">
            <IconContainer>
              <IconButton
                icon={CloseIcon}
                size="large"
                onClick={() => setIsSuccessModalOpen(false)}
                accessibilityLabel="alert-close-btn"
              />
            </IconContainer>
            <Alert
              color="positive"
              title="Successfully deployed all devices"
              description="You have completed merchant onboarding and successfully deployed all the devices"
              marginBottom="spacing.5"
              isDismissible={false}
              isFullWidth
            />
            <Divider />
            <Box display="flex" gap="spacing.3" justifyContent="space-between">
              <Button onClick={handleViewAllDevices} size="medium" isFullWidth variant="tertiary">
                View all Devices
              </Button>
              <Button onClick={handleGoToHomepage} size="medium" isFullWidth variant="primary">
                Go to Homepage
              </Button>
            </Box>
          </Box>
        }
        isOpen={isSuccessModalOpen}
        headerText={''}
        onDismiss={() => setIsSuccessModalOpen(false)}
        snapPoints={[0.8, 0.8, 0.8]}
      />
    </Box>
  );
};

export default DeviceConfiguration;
