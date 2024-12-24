import {
  Badge,
  Box,
  CheckCircleIcon,
  ChevronRightIcon,
  Link,
  Spinner,
  Text,
} from '@razorpay/blade/components';
import React from 'react';
import styled from 'styled-components';

interface DeviceCardProps {
  imageUrl: string;
  name: string;
  isDeployed: boolean;
  type: 'allDevices' | 'deployedDevices';
  plan?: string;
  deviceModelLabel?: string;
  serialNumber?: string;
  deviceId: string;
  isModularLoading: boolean;
  vpa: string;
  currentDeployingDeviceId: string;
  isKycQualified?: boolean;
  handleDeployNow: () => void;
  handleDetailsClick: () => void;
}

const DeviceCardContainer = styled.div``;

export const DeviceCard = ({
  imageUrl,
  name,
  isDeployed,
  type = 'allDevices',
  plan,
  deviceModelLabel,
  serialNumber,
  deviceId,
  isModularLoading,
  currentDeployingDeviceId,
  vpa,
  isKycQualified,
  handleDeployNow,
  handleDetailsClick,
}: DeviceCardProps): JSX.Element => {
  const handleDeployedDevice = () => {
    if (!isDeployed) return;
    handleDetailsClick();
  };

  return (
    <DeviceCardContainer onClick={handleDeployedDevice}>
      <Box display="flex" flexDirection="column" gap="spacing.6">
        <Box display="flex" gap="spacing.4">
          <img src={imageUrl} alt={name} width="68px" height="68px" />
          <Box display="flex" flexDirection="column" gap="spacing.2" flex="1">
            <Box display="flex" justifyContent="space-between" alignItems="baseline">
              <Text>{name}</Text>
              {type === 'deployedDevices' ? (
                <Link
                  icon={ChevronRightIcon}
                  iconPosition="right"
                  onClick={handleDetailsClick}
                  variant="button"
                  size="medium"
                >
                  Details
                </Link>
              ) : null}
            </Box>

            <Text>{plan}</Text>
            {deviceModelLabel ? (
              <Box
                backgroundColor="surface.background.gray.subtle"
                padding={['spacing.3', 'spacing.4']}
                borderRadius="medium"
                width="max-content"
              >
                <Text>{deviceModelLabel}</Text>
              </Box>
            ) : null}
          </Box>
        </Box>
        <Box display="flex" justifyContent="space-between">
          <Box>
            <Box>
              {serialNumber ? (
                <Box display="flex" alignItems="center" justifyContent="flex-start" gap="spacing.2">
                  <Text color="surface.text.gray.muted" weight="regular" size="small">
                    Serial No:
                  </Text>

                  <Text
                    color="surface.text.gray.muted"
                    weight="regular"
                    size="small"
                    truncateAfterLines={1}
                  >
                    {serialNumber}
                  </Text>
                </Box>
              ) : null}
            </Box>
            <Box>
              {vpa ? (
                <Box display="flex" alignItems="center" justifyContent="flex-start" gap="spacing.2">
                  <Text color="surface.text.gray.muted" weight="regular" size="small">
                    VPA:
                  </Text>

                  <Text
                    color="surface.text.gray.muted"
                    weight="regular"
                    size="small"
                    truncateAfterLines={1}
                  >
                    {vpa}
                  </Text>
                </Box>
              ) : null}
            </Box>
          </Box>
          {type === 'allDevices' ? (
            <>
              {isModularLoading && deviceId === currentDeployingDeviceId ? (
                <Spinner accessibilityLabel="deployNowSpinner" size="large" />
              ) : (
                <Box>
                  {isDeployed ? (
                    <Badge color="positive" emphasis="subtle" icon={CheckCircleIcon} size="large">
                      Deployed
                    </Badge>
                  ) : (
                    <Link
                      icon={ChevronRightIcon}
                      iconPosition="right"
                      onClick={handleDeployNow}
                      variant="button"
                      isDisabled={!isKycQualified}
                    >
                      Deploy Now
                    </Link>
                  )}
                </Box>
              )}
            </>
          ) : null}
        </Box>
      </Box>
    </DeviceCardContainer>
  );
};
