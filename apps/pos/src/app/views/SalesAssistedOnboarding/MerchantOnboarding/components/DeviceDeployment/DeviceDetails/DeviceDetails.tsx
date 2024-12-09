import {
  Amount,
  Badge,
  Box,
  CheckIcon,
  ChevronRightIcon,
  Divider,
  Link,
  Text,
} from '@razorpay/blade/components';
import React, { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import ModalWithBottomSheet from 'apps/pos/src/app/components/ModalWithBottomSheet';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import { CurrentDeviceDetails, ModularPayload } from 'apps/pos/src/app/types/modular';
import { MappingStatusType } from 'apps/pos/src/app/utils/deviceDeployment';
import WifiInstructions from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/DeviceDeployment/DeviceConfiguration/components/WifiInstructions';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface DeviceDetailsProps {
  isLoading: boolean;
  mappingStatus: MappingStatusType;
  language: string;
  currentDeviceDetails: CurrentDeviceDetails;
  hideLanguageSettings: boolean;
  hideWifiConfiguration: boolean;
  deviceId: string;
  updateModularConfig: (payload: ModularPayload) => void;
}

const DeviceDetails = ({
  mappingStatus,
  language,
  currentDeviceDetails,
  hideLanguageSettings,
  hideWifiConfiguration,
  deviceId,
}: DeviceDetailsProps): JSX.Element => {
  const [isWifiConfigModalOpen, setIsWifiConfigModalOpen] = useState<boolean>(false);
  const { id: merchantId, step } = useParams();
  const navigate = useNavigate();
  const name = currentDeviceDetails?.display_name;
  const imageUrl = currentDeviceDetails?.icon;
  const plan = currentDeviceDetails?.plan_name;
  const isDeviceTested = mappingStatus.isDeviceTested;
  const isWifiConfigured = mappingStatus.isWifiConfigured;
  const isDeviceMapped = mappingStatus.isDeviceMapped;

  const triggerTrackEvent = (label: string, section: string, subSection: string) => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.LINK,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_DEPLOYMENT,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.EXPLORE_DEVICE_DEPLOYMENT,
        section,
        subSection,
      },
    });
  };

  const handleRedirection = (page: string) => {
    if (page === AvailableComponents.DEVICE_TESTING) {
      triggerTrackEvent('Start Now', 'Device Testing', 'Device Testing');
    } else if (page === AvailableComponents.LANGUAGE_CONFIGURATION) {
      triggerTrackEvent('Change', 'Language setting', 'Language setting');
    } else if (page === AvailableComponents.WIFI_CONFIGURATION) {
      setIsWifiConfigModalOpen(true);
      triggerTrackEvent('Start Now', 'Wifi-Configuration', 'Wifi-Configuration');
      return;
    }
    navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${merchantId}/${step}/${page}`);
  };

  const wifiConfigSuccessCallback = () => {
    setIsWifiConfigModalOpen(false);
  };

  return (
    <Box padding={['spacing.5', 'spacing.6']}>
      <Box display="flex" flexDirection="column" gap="spacing.7" marginBottom="spacing.8">
        <Box display="flex" justifyContent="space-between">
          <Text color="surface.text.gray.muted" weight="regular">
            MID: {merchantId}
          </Text>
          {isDeviceMapped ? (
            <Badge color="positive" emphasis="subtle" icon={CheckIcon} size="large">
              Deployed
            </Badge>
          ) : null}
        </Box>
        <Box display="flex" gap="spacing.4">
          <img src={imageUrl} alt={name} width="68px" height="68px" />
          <Box display="flex" flexDirection="column" gap="spacing.2" flex="1">
            <Box display="flex" justifyContent="space-between" alignItems="baseline">
              <Text weight="semibold">{name}</Text>
              <Amount value={currentDeviceDetails.setup_charge} />
            </Box>

            <Text color="surface.text.gray.subtle" weight="regular">
              {plan}
            </Text>

            <Box
              backgroundColor="surface.background.gray.subtle"
              padding={['spacing.3', 'spacing.4']}
              borderRadius="medium"
              width="max-content"
            >
              <Text>{currentDeviceDetails?.display_label}</Text>
            </Box>
          </Box>
        </Box>
      </Box>
      <Divider />
      <Box display="flex" flexDirection="column" gap="spacing.7" marginTop="spacing.8">
        {!hideLanguageSettings ? (
          <Box display="flex" justifyContent="space-between">
            <Box display="flex">
              <Text color="surface.text.gray.subtle" weight="medium">
                Language
              </Text>
              <Text color="surface.text.gray.subtle" weight="regular" marginLeft="spacing.3">
                {language}
              </Text>
            </Box>
            <Link
              icon={ChevronRightIcon}
              iconPosition="right"
              onClick={() => handleRedirection(AvailableComponents.LANGUAGE_CONFIGURATION)}
              variant="button"
            >
              Change
            </Link>
          </Box>
        ) : null}
        {currentDeviceDetails?.device_serial ? (
          <Box display="flex">
            <Text color="surface.text.gray.subtle" weight="medium">
              Device Serial Number
            </Text>
            <Text color="surface.text.gray.subtle" weight="regular" marginLeft="spacing.3">
              {currentDeviceDetails?.device_serial}
            </Text>
          </Box>
        ) : null}
        <Box display="flex">
          <Text color="surface.text.gray.subtle" weight="medium">
            Device VPA
          </Text>
          <Text color="surface.text.gray.subtle" weight="regular" marginLeft="spacing.3">
            {currentDeviceDetails?.mapped_vpa}
          </Text>
        </Box>
        <Box display="flex" justifyContent="space-between">
          <Text color="surface.text.gray.subtle" weight="medium">
            Device Testing
          </Text>
          {isDeviceTested ? (
            <Badge color="positive" emphasis="subtle" icon={CheckIcon} size="large">
              Tested
            </Badge>
          ) : (
            <Link
              icon={ChevronRightIcon}
              iconPosition="right"
              onClick={() => handleRedirection(AvailableComponents.DEVICE_TESTING)}
              variant="button"
            >
              Start Now
            </Link>
          )}
        </Box>
        {!hideWifiConfiguration ? (
          <Box display="flex" justifyContent="space-between">
            <Text color="surface.text.gray.subtle" weight="medium">
              Wifi-Configuration
            </Text>
            {isWifiConfigured ? (
              <Badge color="positive" emphasis="subtle" icon={CheckIcon} size="large">
                Tested
              </Badge>
            ) : (
              <Link
                icon={ChevronRightIcon}
                iconPosition="right"
                onClick={() => handleRedirection(AvailableComponents.WIFI_CONFIGURATION)}
                variant="button"
                testID="wifi-configuration-start-now"
              >
                Start Now
              </Link>
            )}
          </Box>
        ) : null}
      </Box>
      <ModalWithBottomSheet
        content={
          <WifiInstructions deviceId={deviceId} onWifiConfigSuccess={wifiConfigSuccessCallback} />
        }
        isOpen={isWifiConfigModalOpen}
        headerText={''}
        onDismiss={() => setIsWifiConfigModalOpen(false)}
        snapPoints={[0.8, 0.8, 0.8]}
      />
    </Box>
  );
};

export default DeviceDetails;
