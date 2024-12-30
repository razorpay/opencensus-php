import React from 'react';
import {
  Box,
  Button,
  Heading,
  InfoIcon,
  RefreshIcon,
  Text,
  ToastContainer,
  useTheme,
} from '@razorpay/blade/components';

import DeviceCard from './DeviceCard';
import DeviceCardSkeleton from './DeviceCard/DeviceCardSkeleton';
import { useFetchDevices } from './hooks';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const MyDevices = () => {
  const { data: deviceData, isLoading: isDevicesLoading, refetch } = useFetchDevices();
  const devices = deviceData?.data?.items || [];
  const {
    theme: { breakpoints },
  } = useTheme();

  !isDevicesLoading &&
    analyticsTrack({
      objectName: 'My Devices',
      actionName: 'Render',
      screen: 'My Devices',
      properties: {
        event_name: 'my_devices_page',
        devices_data: devices,
        isDeviceAvailble: devices.length !== 0,
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
      },
    });
  return (
    <>
      <Box backgroundColor="surface.background.gray.subtle" padding="spacing.6">
        <Box
          backgroundColor="surface.background.gray.intense"
          paddingY="spacing.4"
          paddingX="spacing.7"
        >
          <Heading size="large">My Devices ({devices.length})</Heading>
        </Box>
        <Box
          display="flex"
          flexDirection="column"
          backgroundColor="surface.background.gray.intense"
          gap="spacing.7"
          padding="spacing.7"
        >
          {isDevicesLoading ? (
            <DeviceCardSkeleton />
          ) : (
            devices.map((device) => <DeviceCard device={device} key={device.dsn} />)
          )}
          {devices.length === 0 && !isDevicesLoading && (
            <Box
              paddingY="spacing.11"
              display="flex"
              justifyContent="center"
              flexDirection="column"
              alignItems="center"
              minHeight={{
                base: '100%',
                m: `${breakpoints.m}px`,
                l: `${breakpoints.m}px`,
                xl: `${breakpoints.m}px`,
              }}
            >
              <Box
                width="50%"
                display="flex"
                justifyContent="center"
                flexDirection="column"
                alignItems="center"
              >
                <InfoIcon />
                <Text
                  color="surface.text.gray.normal"
                  size="medium"
                  weight="medium"
                  marginTop="spacing.5"
                  marginBottom="spacing.2"
                >
                  Error Loading the page!
                </Text>
                <Text
                  textAlign="center"
                  color="surface.text.gray.subtle"
                  size="small"
                  weight="regular"
                >
                  We could not load this page. Please try again after sometime or contact support.
                </Text>
                <Button
                  iconPosition="right"
                  icon={RefreshIcon}
                  marginTop="spacing.5"
                  variant="secondary"
                  onClick={() => {
                    refetch();
                  }}
                >
                  Retry
                </Button>
              </Box>
            </Box>
          )}
        </Box>
      </Box>
      <ToastContainer />
    </>
  );
};

export default MyDevices;
