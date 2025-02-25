import { toTitleCase } from '@libs/shared-utils';
import { ModularPayload, PlanConfig } from '../types/modular';
import {
  AvailableDevicePlans,
  MODULAR_DEVICE_FIELDS,
  DeviceFee,
  DeviceFeeOptions,
  DeviceOptionalFeature,
  DevicePlanCharge,
} from 'apps/pos/src/app/types/DeviceSelection';

export const DeviceFees: DeviceFee[] = [
  {
    title: 'Setup Fee',
    field: MODULAR_DEVICE_FIELDS.DEVICE_SETUP_FEE_TYPE,
    customAmountField: MODULAR_DEVICE_FIELDS.DEVICE_SETUP_CUSTOM_FEE_AMOUNT,
  },
  {
    title: (devicePlan: string) => `${toTitleCase(devicePlan)} Rental Charges`,
    field: MODULAR_DEVICE_FIELDS.DEVICE_RENTAL_TYPE,
    customAmountField: MODULAR_DEVICE_FIELDS.DEVICE_RENTAL_CUSTOM_AMOUNT,
    isHidden: (plan: PlanConfig) => {
      return !plan?.rentalCharge;
    },
  },
];

const RENTAL_CHARGE_FEATURE: DeviceOptionalFeature = {
  title: 'Collecting Rental Charges in Advance (in months)',
  field: MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE,
  customInputField: MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_PERIOD_FIELD,
};

export const DeviceOptionalFeatures: DeviceOptionalFeature[] = [RENTAL_CHARGE_FEATURE];

export const DevicePlanAvailableCharges: DevicePlanCharge[] = [
  {
    name: 'Setup Fee',
    key: 'setupFee',
  },
  {
    name: 'Rental Charge',
    key: 'rentalCharge',
  },
  {
    name: 'One time charge',
    key: 'oneTimeCharge',
  },
];

export const DeviceFeeTypes: DeviceFeeOptions[] = [
  {
    name: 'Standard',
    key: 'standard',
  },
  {
    name: 'Custom',
    key: 'custom',
  },
];

export const MODULAR_FLAGS: Record<string, ModularPayload> = {
  ADD_ITEM_TO_CART: {
    [MODULAR_DEVICE_FIELDS.DEVICE_ADD_TO_CART_FIELD]: true,
  },
  CONFIRM_DEVICE_SELECTION: {
    [MODULAR_DEVICE_FIELDS.DEVICE_CONFIRM_DEVICE_FIELD]: true,
  },
  DELETE_CART_ITEM: {
    [MODULAR_DEVICE_FIELDS.DEVICE_DELETE_PRODUCT_FIELD]: true,
  },
  CONFIRM_ORDER: {
    [MODULAR_DEVICE_FIELDS.DEVICE_ORDER_CONFIRMATION_FIELD]: true,
  },
};

export const RentalChargeFrequencyLabels: Record<Exclude<AvailableDevicePlans, 'lifetime'>, string> = {
  monthly: 'Collecting Rental Charges in Advance (in months)',
  quarterly: 'Collecting Rental Charges in Advance (in quarters)',
  half_yearly: 'Collecting Rental Charges in Advance (in half years)',
  yearly: 'Collecting Rental Charges in Advance (in years)',
}
