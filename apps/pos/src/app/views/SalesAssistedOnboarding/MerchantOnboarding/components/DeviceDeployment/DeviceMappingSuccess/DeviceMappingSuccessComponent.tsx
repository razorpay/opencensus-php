import { Box, Button, Heading, Link, Text } from '@razorpay/blade/components';
import React from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { DeviceMappingSuccessContainer } from '../DeviceMapping/DeviceMappingScannerComponent/styles';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import SuccessIcon from 'apps/pos/src/assets/paymentSuccess.svg';

interface DeviceMappingSuccessComponentProps {
  deviceName: string;
  deviceSerialNumber: string;
  mappedVpa: string;
}

export const DeviceMappingSuccessComponent = ({
  deviceName,
  deviceSerialNumber,
  mappedVpa,
}: DeviceMappingSuccessComponentProps): JSX.Element => {
  const { id, step } = useParams();
  const navigate = useNavigate();

  const handleDeployAnotherDevice = () => {
    navigate(
      `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${AvailableComponents.DEVICE_DEPLOYMENT_LIST}`,
    );
  };

  const handleCompleteDeviceSetup = () => {
    navigate(
      `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${AvailableComponents.DEVICE_CONFIGURATION}`,
    );
  };

  return (
    <DeviceMappingSuccessContainer>
      <Box
        height="100%"
        display="flex"
        justifyContent="center"
        flexDirection="column"
        alignItems="center"
      >
        <Box marginBottom="spacing.5">
          <img src={SuccessIcon} alt="success-icon" height="100px" />
        </Box>
        <Heading
          weight="semibold"
          color="interactive.text.positive.normal"
          marginBottom="spacing.5"
          textAlign="center"
        >
          Device Mapped Successfully
        </Heading>
        <Box marginBottom="spacing.5">
          <Box display="flex" alignItems="center" gap="spacing.3">
            <Text weight="semibold" color="surface.text.gray.subtle">
              Model Details
            </Text>
            <Text color="surface.text.gray.subtle">{deviceName}</Text>
          </Box>
          {mappedVpa ? (
            <Box display="flex" alignItems="center" gap="spacing.3">
              <Text weight="semibold" color="surface.text.gray.subtle">
                Device VPA
              </Text>
              <Text color="surface.text.gray.subtle">{mappedVpa}</Text>
            </Box>
          ) : null}
          {deviceSerialNumber ? (
            <Box display="flex" alignItems="center" gap="spacing.3">
              <Text weight="semibold" color="surface.text.gray.subtle">
                Serial Number
              </Text>
              <Text color="surface.text.gray.subtle">{deviceSerialNumber}</Text>
            </Box>
          ) : null}
        </Box>
        <Button onClick={handleCompleteDeviceSetup} marginBottom="spacing.3">
          Complete Device Set-up
        </Button>
        <Link onClick={handleDeployAnotherDevice} variant="button">
          Deploy Another Device
        </Link>
      </Box>
    </DeviceMappingSuccessContainer>
  );
};
