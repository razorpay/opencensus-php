import React, { useMemo, useState } from 'react';
import {
  Box,
  ChipGroup,
  Chip,
  Text,
  TextInput,
  Button,
  SearchIcon,
  Divider,
} from '@razorpay/blade/components';

import { useNavigate, useParams } from 'react-router-dom';
import { DeviceCard } from './components/DeviceCard';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { DEVICE_DEPLOYMENT_STATUS } from 'apps/pos/src/app/utils/deviceDeployment';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

export interface DeviceType {
  details_page_name?: string;
  device_model: string;
  device_order_item_id: string;
  device_serial: string;
  display_label: string;
  display_name: string;
  id: string;
  mapped_vpa: string;
  mapping_status: string;
  plan_name: string;
  setup_charge: number;
  icon: string;
}
interface DevicesListProps {
  title: string;
  type: 'allDevices' | 'deployedDevices';
  isAllDevicesDeployed?: boolean;
  devices: DeviceType[];
  isModularLoading: boolean;
  currentDeployingDeviceId?: string;
  handleDeployNow: (device: DeviceType) => void;
}

enum StatusType {
  ALL = 'all',
  INACTIVE = 'inactive',
  DEPLOYED = 'deployed',
}

export const DevicesList = ({
  title,
  type,
  isAllDevicesDeployed = false,
  devices,
  isModularLoading,
  currentDeployingDeviceId = '',
  handleDeployNow,
}: DevicesListProps): JSX.Element => {
  const navigate = useNavigate();
  const { id } = useParams();
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [status, setStatus] = useState<StatusType>(StatusType.ALL);
  const filteredDevices = useMemo(() => {
    return devices.filter((item) => {
      const matchesSearchQuery = searchQuery
        ? item?.display_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
          item?.device_serial.includes(searchQuery)
        : true;
      const filteredStatus =
        status === StatusType.ALL
          ? true
          : item.mapping_status.toLowerCase() === status.toLowerCase();
      return filteredStatus && matchesSearchQuery;
    });
  }, [devices, searchQuery, status]);

  const triggerTrackEvent = (label: string, subSection: string) => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_DEPLOYMENT,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.EXPLORE_DEVICE_DEPLOYMENT,
        section: 'Devices Deployed',
        subSection,
      },
    });
  };

  const handleBackToDashboard = () => {
    triggerTrackEvent('Back to Dashboard', 'Devices Deployed');
    navigate(`/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}`);
  };

  const handleChipChange = (values: StatusType[]) => {
    setStatus(values[0]);
  };

  const handleDeviceSearch = () => {
    triggerTrackEvent('Search Icon', 'Search Device');
    setSearchQuery(searchQuery);
  };

  return (
    <Box display="flex" flexDirection="column" gap="spacing.8" marginTop="spacing.8">
      <Text weight="semibold" size="large">
        {title}
      </Text>
      <Box display="flex" gap="spacing.4">
        <Box flex="1">
          <TextInput
            label="Search Device"
            labelPosition="top"
            name="searchDevice"
            onChange={({ value = '' }) => {
              setSearchQuery(value);
            }}
            value={searchQuery}
            placeholder="Device Name/ Serial Number"
            size="medium"
          />
        </Box>
        <Box display="flex" flexDirection="column" justifyContent="flex-end">
          <Button onClick={handleDeviceSearch} icon={SearchIcon} />
        </Box>
      </Box>
      {type === 'allDevices' ? (
        <Box gap="spacing.4">
          <Text
            weight="medium"
            color="interactive.text.gray.subtle"
            marginBottom="spacing.4"
            size="small"
          >
            Status
          </Text>
          <ChipGroup
            accessibilityLabel="Status"
            value={status}
            onChange={({ values }) => handleChipChange(values as StatusType[])}
          >
            <Chip value={StatusType.ALL}>All</Chip>
            <Chip value={StatusType.INACTIVE}>Inactive</Chip>
            <Chip value={StatusType.DEPLOYED}>Deployed</Chip>
          </ChipGroup>
        </Box>
      ) : null}
      {filteredDevices.map((device) => {
        return (
          <>
            <DeviceCard
              isModularLoading={isModularLoading}
              key={device.id}
              deviceId={device.id}
              type={type}
              serialNumber={device?.device_serial}
              vpa={device?.mapped_vpa}
              name={device.display_name}
              isDeployed={device.mapping_status === DEVICE_DEPLOYMENT_STATUS.DEPLOYED}
              imageUrl={device.icon}
              plan={device.plan_name}
              deviceModelLabel={device.display_label}
              handleDeployNow={() => handleDeployNow(device)}
              currentDeployingDeviceId={currentDeployingDeviceId}
            />
            <Divider />
          </>
        );
      })}
      {isAllDevicesDeployed ? (
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
          <Button onClick={handleBackToDashboard} isFullWidth>
            Back to Dashboard
          </Button>
        </Box>
      ) : null}
    </Box>
  );
};
