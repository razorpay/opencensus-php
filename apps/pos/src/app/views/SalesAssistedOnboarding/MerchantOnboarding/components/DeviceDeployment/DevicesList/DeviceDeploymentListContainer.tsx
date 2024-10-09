import React, { useEffect, useState } from 'react';
import { Box, Tabs, TabItem, TabList, TabPanel, Spinner } from '@razorpay/blade/components';
import { useNavigate, useParams } from 'react-router-dom';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { ScannerType } from '../DeviceMapping/DeviceMappingScannerComponent/DeviceMappingScannerContainer';
import { DevicesList, DeviceType } from './DevicesList';
import { EmptyCart } from './components/EmptyCart';
import useOnboardingContext from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/providers/useOnboardingContext';
import {
  DEVICE_DEPLOYMENT_STATUS,
  getDeviceListDataFromModularConfig,
} from 'apps/pos/src/app/utils/deviceDeployment';
import { DEVICE_DEPLOYMENT_FIELDS } from 'apps/pos/src/app/types/DeviceDeployment';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { AvailableComponents, MODULES } from 'apps/pos/src/app/types/common';
import { DeviceModel } from 'apps/pos/src/app/utils/deviceSelection';
import { sentryHub } from 'apps/pos/src/bootstrap/Wrapper/Wrapper';
import PageError from 'apps/pos/src/app/components/PageError';

const DeviceDeploymentList = (): JSX.Element | null => {
  const { states, handlers } = useOnboardingContext();
  const { modularConfig, isUpdateModularLoading } = states;
  const { updateModularConfig } = handlers;
  const navigate = useNavigate();
  const { id, step } = useParams();
  const { devicesList, isAllDevicesDeployed } = getDeviceListDataFromModularConfig({
    modularConfig,
  });
  const [filteredDevicesList, setFilteredDevicesList] = useState(devicesList);
  const [currentDeployingDeviceId, setCurrentDeployingDeviceId] = useState<string>('');

  useEffect(() => {
    const payload = {
      [DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_ACTION_FIELD]:
        DEVICE_DEPLOYMENT_FIELDS.REFRESH_DEVICE_DEPLOYMENT_LIST,
    };
    updateModularConfig(payload);
  }, []);

  const handleTabChange = (tabValue: string) => {
    if (devicesList) {
      const filteredDevices: any = devicesList.filter((_device: any) => {
        if (tabValue === 'deployedDevices') {
          return _device?.mapping_status === DEVICE_DEPLOYMENT_STATUS.DEPLOYED;
        }
        return _device;
      });
      setFilteredDevicesList(filteredDevices);
    }
  };

  const deviceIDSuccessCallback = (device: DeviceType) => {
    const isStickerAndStandee = device?.display_name === DeviceModel.STICKER_AND_STANDEE;
    navigate(
      `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${
        AvailableComponents.DEVICE_MAPPING_SCANNER
      }?deviceId=${device?.id}&scannerType=${
        isStickerAndStandee ? ScannerType.QR_CODE : ScannerType.BAR_CODE
      }`,
    );
  };
  const handleDeployNow = (device: DeviceType) => {
    setCurrentDeployingDeviceId(device?.id);
    const payload = {
      [DEVICE_DEPLOYMENT_FIELDS.CURRENT_DEVICE_ID_FIELD]: device.id,
      [DEVICE_DEPLOYMENT_FIELDS.MODULAR_CALLBACK]: () => deviceIDSuccessCallback(device),
    };
    updateModularConfig(payload);
  };

  if (!modularConfig) return null;
  if (isUpdateModularLoading && !currentDeployingDeviceId) {
    return (
      <Box height="100vh" display="flex" alignItems="center" justifyContent="center">
        <Spinner accessibilityLabel="device-list-spinner" size="large" />
      </Box>
    );
  }

  return (
    <ErrorBoundary
      sentryHub={sentryHub?.sentryHub}
      rank={errorService.ErrorRank.P0}
      tags={{ module: MODULES.DEVICE_DEPLOYMENT }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <Box padding="spacing.6">
        <Tabs
          onChange={(val) => {
            handleTabChange(val);
          }}
        >
          <TabList>
            <TabItem value="allDevices">All Devices</TabItem>
            <TabItem value="deployedDevices">Deployed Devices</TabItem>
          </TabList>
          <TabPanel value="allDevices">
            {isAllDevicesDeployed ? (
              <EmptyCart />
            ) : (
              <DevicesList
                currentDeployingDeviceId={currentDeployingDeviceId}
                isModularLoading={isUpdateModularLoading}
                type="allDevices"
                title="Choose Devices to Deploy"
                devices={filteredDevicesList}
                handleDeployNow={handleDeployNow}
              />
            )}
          </TabPanel>
          <TabPanel value="deployedDevices">
            <DevicesList
              isModularLoading={isUpdateModularLoading}
              type="deployedDevices"
              title="Devices Deployed"
              isAllDevicesDeployed={isAllDevicesDeployed}
              devices={filteredDevicesList}
              handleDeployNow={handleDeployNow}
            />
          </TabPanel>
        </Tabs>
      </Box>
    </ErrorBoundary>
  );
};

export default DeviceDeploymentList;
