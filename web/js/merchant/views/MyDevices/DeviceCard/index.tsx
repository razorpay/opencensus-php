import React from 'react';
import {
  BarChartIcon,
  BatteryChargingIcon,
  Divider,
  Box,
  Heading,
  Text,
} from '@razorpay/blade/components';

import { useMobile } from 'common/hooks/useMobile';

import LanguageSelector from './LanguageSelector';
import { DeviceImage } from './styled';
import DeviceStats from '../components/DeviceStats';
import DeviceStatusBadge from '../components/DeviceStatusBadge';
import { useDeviceLanguage } from '../hooks';
import DeviceImageSource from '../image.png';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const DeviceCard = ({ device, key }) => {
  const { mutate: updateDeviceLanguage } = useDeviceLanguage();
  const isMobile = useMobile();

  const handleLanguageSelect = (id: string, language: string) => {
    updateDeviceLanguage({
      id,
      settings: {
        deviceLanguagePref: language,
      },
    });

    analyticsTrack({
      objectName: 'My Devices',
      actionName: 'clicked',
      screen: 'My Devices',
      properties: {
        event_name: 'my_devices_select_lang',
        language,
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
      },
    });
  };

  const getDeviceNetwork = (network: number) => {
    if (network >= 71) {
      return 'High';
    }
    if (network < 70 && network > 30) {
      return 'Medium';
    }

    return 'Low';
  };

  return (
    <Box
      display="flex"
      borderRadius="large"
      borderColor="surface.border.gray.muted"
      borderWidth="thinner"
      backgroundColor="surface.background.gray.intense"
      flexDirection="column"
      position="relative"
      height="auto"
      key={device.dsn}
    >
      <DeviceImage src={DeviceImageSource} alt="device-image" />
      <Box
        padding="spacing.10"
        paddingBottom="spacing.5"
        paddingTop={isMobile ? 'spacing.7' : 'spacing.10'}
        paddingLeft={isMobile ? '32vw' : '32%'}
        display="flex"
        flex={1}
        flexDirection="column"
        justifyContent="center"
        position="relative"
        height="auto"
        key={key}
      >
        <Heading color="surface.text.gray.normal" size="2xlarge">
          J&K Soundbox
        </Heading>
        <Text
          size={isMobile ? 'small' : 'large'}
          color="surface.text.gray.normal"
          weight="semibold"
        >
          {device.id}
        </Text>
        <DeviceStatusBadge isActive={device?.status?.toLowerCase() === 'active'} />
      </Box>
      <Box
        flex="1"
        padding="spacing.9"
        backgroundColor="surface.background.gray.subtle"
        display="flex"
        alignItems="center"
        borderBottomLeftRadius="large"
        borderBottomRightRadius="large"
        borderTopColor="surface.border.gray.muted"
        borderTopWidth="thin"
        paddingLeft={useMobile(['s']) ? 'spacing.9' : '32%'}
        paddingTop={useMobile(['s']) ? 'spacing.11' : 'spacing.9'}
        flexDirection={useMobile(['s']) ? 'column' : 'row'}
      >
        <Box
          width="100%"
          justifyContent="space-between"
          display="flex"
          alignItems="center"
          flexWrap="wrap"
          gap="spacing.8"
        >
          <Box display="flex" flexDirection="column">
            <Text color="surface.text.gray.subtle">DSN</Text>
            <Text size="large" color="surface.text.gray.normal" weight="semibold">
              {device.device_serial || ''}
            </Text>
          </Box>
          <LanguageSelector
            availableLanguage={device.supported_languages}
            selectedLanguage={device.language}
            onLanguageSelect={(language) => handleLanguageSelect(device.id, language)}
          />
        </Box>
        {isMobile ? (
          <Divider orientation="horizontal" marginY="spacing.6" width="100%" />
        ) : (
          <Divider orientation="vertical" marginX="spacing.9" />
        )}
        <Box
          display="flex"
          gap="spacing.8"
          justifyContent="space-around"
          alignItems="center"
          width="100%"
        >
          <DeviceStats
            Icon={BarChartIcon}
            name="Network"
            data={getDeviceNetwork(device.stats.network_strength)}
          />
          <DeviceStats
            Icon={BatteryChargingIcon}
            name="Battery"
            data={`${device.stats.battery_percentage}%`}
          />
        </Box>
      </Box>
    </Box>
  );
};

export default DeviceCard;
