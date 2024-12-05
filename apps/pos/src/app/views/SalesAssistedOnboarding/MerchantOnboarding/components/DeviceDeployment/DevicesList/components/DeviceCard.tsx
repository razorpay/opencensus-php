import React from 'react';
import {
  Box,
  Link,
  ChevronRightIcon,
  Text,
  Badge,
  CheckCircleIcon,
  Spinner,
} from '@razorpay/blade/components';
import { useNavigate, useParams } from 'react-router-dom';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';
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
}: DeviceCardProps): JSX.Element => {
  const { id, step } = useParams();
  const navigate = useNavigate();

  const handleDetailsClick = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Details',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_DEPLOYMENT,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.EXPLORE_DEVICE_DEPLOYMENT,
        section: 'Devices Deployed',
        subSection: name,
      },
    });
    navigate(
      `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${AvailableComponents.DEVICE_DETAILS}?deviceId=${deviceId}`,
    );
  };

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
