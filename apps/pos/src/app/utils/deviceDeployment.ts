import { COMPLETED, PENDING } from './agreementSigning';
import { isBooleanValue, isStringValue } from './modularTypeResolvers';
import { DEVICE_DEPLOYMENT_FIELDS } from 'apps/pos/src/app/types/DeviceDeployment';
import {
  CurrentDeviceDetails,
  MerchantModularOnboardingDetailsSuccessResponse,
} from 'apps/pos/src/app/types/modular';
import {
  getComponentFromStep,
  getFieldFromComponent,
  getProgressFromModularStep,
} from 'apps/pos/src/app/utils/modularConfig';
import { MILESTONE_NAME_FIELDS } from '../types/DeviceSelection';
import { DeviceModel } from './deviceSelection';

interface ModularConfigType {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

interface GetDeviceListDataFromModularConfig {
  devicesList: CurrentDeviceDetails[];
  isAllDevicesDeployed: boolean;
}
export interface LanguageOption {
  label: string;
  value: string;
}

interface LanguageDetailsType {
  deviceId: string;
  deviceName: string;
  languageList: LanguageOption[];
  description: string;
}

interface AmountTestingType {
  deviceId: string;
  deviceName: string;
  defaultTestingAmount: string;
  description: string;
}

export interface MappingStatusType {
  isDeviceMapped: boolean;
  isDeviceTested: boolean;
  isWifiConfigured: boolean;
}
interface DeviceConfigType {
  deviceId: string;
  mappingStatus: MappingStatusType;
  language: string;
  deviceName: string;
  hideLanguageSettings: boolean;
  hideWifiConfiguration: boolean;
  hideDeviceTesting: boolean;
  currentDeviceDetails: CurrentDeviceDetails;
}

export enum DEVICE_DEPLOYMENT_STATUS {
  INACTIVE = 'INACTIVE',
  DEPLOYED = 'DEPLOYED',
}

type DeviceDeploymentStepStatus = 'pending' | 'completed';
interface DeviceDeploymentStatusType {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

export const getDeviceDeploymentStatus = ({
  modularConfig,
}: DeviceDeploymentStatusType): DeviceDeploymentStepStatus => {
  if (!modularConfig) return PENDING;
  const isDeviceDeploymentComplete =
    getProgressFromModularStep({
      modularConfig,
      step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    }) === COMPLETED;
  if (isDeviceDeploymentComplete) return COMPLETED;
  return PENDING;
};

export const getDeviceListDataFromModularConfig = ({
  modularConfig,
}: ModularConfigType): GetDeviceListDataFromModularConfig => {
    // Run github actions
  const component = getComponentFromStep({
    modularConfig,
    step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    component: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
  });
  let devicesList;
  if (component) {
    devicesList = component?.fields.find(
      (_component) =>
        _component.name === DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_DETAILS_LIST_FIELD,
    );
  }

  let isAllDevicesDeployed = false;
  if (devicesList?.meta?.deviceDeploymentDetailsList) {
    isAllDevicesDeployed = devicesList?.meta?.jsonValue?.device_deployment_details_list.every(
      (_device) => _device.mapping_status === DEVICE_DEPLOYMENT_STATUS.DEPLOYED,
    );
  }

  return {
    devicesList: devicesList?.meta?.jsonValue?.device_deployment_details_list ?? [],
    isAllDevicesDeployed,
  };
};

export const getDeviceMappingDetailsFromModularConfig = ({
  modularConfig,
}: ModularConfigType): CurrentDeviceDetails | null => {
  if (!modularConfig) return null;
  const serialNumberData = getFieldFromComponent({
    modularConfig,
    step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    component: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
    fieldName: DEVICE_DEPLOYMENT_FIELDS.CURRENT_DEVICE_ID_FIELD,
  });
  const deviceDetails = serialNumberData?.meta?.jsonValue?.device_deployment_details;
  if (!deviceDetails) return null;
  return deviceDetails;
};

export const getLanguageDetailsFromModularConfig = ({
  modularConfig,
}: ModularConfigType): LanguageDetailsType | null => {
  if (!modularConfig) return null;
  const languageFieldData = getFieldFromComponent({
    modularConfig,
    step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    component: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
    fieldName: DEVICE_DEPLOYMENT_FIELDS.DEVICE_LANGUAGE_FIELD,
  });
  const deviceDetails = getDeviceMappingDetailsFromModularConfig({ modularConfig });
  return {
    deviceId: deviceDetails?.id || '',
    deviceName: deviceDetails?.details_page_name || '',
    languageList: languageFieldData?.meta?.options || [],
    description: languageFieldData?.meta?.description || '',
  };
};

export const getTestingAmountDetailsFromModularConfig = ({
  modularConfig,
}: ModularConfigType): AmountTestingType | null => {
  if (!modularConfig) return null;
  const testingAmountData = getFieldFromComponent({
    modularConfig,
    step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    component: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
    fieldName: DEVICE_DEPLOYMENT_FIELDS.DEVICE_TESTING_AMOUNT_FIELD,
  });

  const deviceDetails = getDeviceMappingDetailsFromModularConfig({ modularConfig });
  if (!deviceDetails) return null;
  return {
    deviceId: deviceDetails?.id || '',
    deviceName: deviceDetails.details_page_name || '',
    defaultTestingAmount: testingAmountData?.meta?.defaultValue || '',
    description: testingAmountData?.meta?.description || '',
  };
};

export const getDeviceConfigurationDetailsFromModularConfig = ({
  modularConfig,
}: ModularConfigType): DeviceConfigType | null => {
  if (!modularConfig) return null;

  const wifiConfigField = getFieldFromComponent({
    modularConfig,
    step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    component: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
    fieldName: DEVICE_DEPLOYMENT_FIELDS.DEVICE_WIFI_CONFIGURATION_STATUS_FIELD,
  });

  const currentDeviceField = getFieldFromComponent({
    modularConfig,
    step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    component: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
    fieldName: DEVICE_DEPLOYMENT_FIELDS.CURRENT_DEVICE_ID_FIELD,
  });
  const currentDeviceDetails = currentDeviceField?.meta?.jsonValue?.device_deployment_details;
  if (!currentDeviceDetails) return null;

  const deviceTestingField = getFieldFromComponent({
    modularConfig,
    step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    component: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
    fieldName: DEVICE_DEPLOYMENT_FIELDS.DEVICE_TESTING_AMOUNT_RECEIVED_FIELD,
  });

  const languageFieldData = getFieldFromComponent({
    modularConfig,
    step: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_STEP,
    component: DEVICE_DEPLOYMENT_FIELDS.DEVICE_DEPLOYMENT_COMPONENT,
    fieldName: DEVICE_DEPLOYMENT_FIELDS.DEVICE_LANGUAGE_FIELD,
  });

  return {
    mappingStatus: {
      isDeviceMapped: currentDeviceDetails?.mapping_status === DEVICE_DEPLOYMENT_STATUS.DEPLOYED,
      isDeviceTested: isBooleanValue(deviceTestingField) ? deviceTestingField?.booleanValue : false,
      isWifiConfigured: isBooleanValue(wifiConfigField) ? wifiConfigField?.booleanValue : false,
    },
    deviceId: currentDeviceDetails?.id || '',
    language: isStringValue(languageFieldData) ? languageFieldData?.stringValue : 'English',
    deviceName: currentDeviceDetails?.details_page_name || '',
    hideLanguageSettings:
      languageFieldData?.isHidden ||
      currentDeviceDetails.display_name === DeviceModel.STICKER_AND_STANDEE, //TODO: revert once BE SDK for isHidden is fixed.
    hideWifiConfiguration:
      wifiConfigField?.isHidden ||
      currentDeviceDetails.display_name === DeviceModel.STICKER_AND_STANDEE,
    hideDeviceTesting:
      deviceTestingField?.isHidden ||
      currentDeviceDetails.display_name === DeviceModel.STICKER_AND_STANDEE,
    currentDeviceDetails,
  };
};

export const isKycQualifiedEkyc = (posActivationStatus: string | null): boolean => {
  return ['ACTIVATED', 'KYC_QUALIFIED_STB'].includes(posActivationStatus || '');
};

export const isDeviceDeploymentFlowActivated = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null,
): boolean => {
  return !!modularConfig?.workflowData?.milestones?.find(
    (milestone) => milestone.name === MILESTONE_NAME_FIELDS.PARTNER_DEVICE_DEPLOYMENT_MILESTONE,
  );
};
